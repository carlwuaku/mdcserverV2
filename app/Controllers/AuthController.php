<?php

namespace App\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\Utils;
use App\Models\PermissionsModel;
use App\Models\RolePermissionsModel;
use App\Models\RolesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use App\Models\UsersModel;
use CodeIgniter\Database\MigrationRunner;
use ReCaptcha\ReCaptcha as ReCaptchaReCaptcha;
use App\Helpers\CacheHelper;
use Vectorface\GoogleAuthenticator;
use CodeIgniter\I18n\Time;
use App\Models\Auth\PasswordResetTokenModel;
use App\Models\Auth\PasswordResetAttemptModel;
use App\Helpers\EmailConfig;
use App\Helpers\EmailHelper;
use App\Helpers\TemplateEngineHelper;
use App\Traits\CacheInvalidatorTrait;


/**
 * @OA\Tag(
 *     name="Authentication",
 *     description="Authentication and user management operations"
 * )
 * @OA\Tag(
 *     name="User Management",
 *     description="Operations for managing users, roles, and permissions"
 * )
 */
class AuthController extends ResourceController
{
    use CacheInvalidatorTrait;

    protected $passwordResetTokenModel;
    protected $passwordResetAttemptModel;

    protected $userModel;

    public function __construct()
    {
        $this->passwordResetTokenModel = new PasswordResetTokenModel();
        $this->passwordResetAttemptModel = new PasswordResetAttemptModel();
        $this->userModel = new UsersModel();
    }

    public function appSettings()
    {
        $userId = auth("tokens")->id();
        $cacheKey = $userId ? "app_settings_{$userId}" : "app_settings";
        return CacheHelper::remember("$cacheKey", function () {
            //read the data from app-settings.json at the root of the project
            try {
                $settings = ['appName', 'appVersion', 'appLongName', 'logo', 'whiteLogo', 'loginBackground'];
                //if the user is logged in, add more settings
                if (auth("tokens")->loggedIn()) {
                    $settings = array_merge($settings, [
                        'sidebarMenu',
                        'dashboardMenu',
                        'searchTypes',
                        'renewalBasicStatisticsFilterFields',
                        'basicStatisticsFilterFields',
                        'advancedStatisticsFilterFields',
                        'basicStatisticsFields',
                        'licenseTypes',
                        'cpdFilterFields',
                        'housemanship',
                        'examinations',
                        'payments'
                    ]);
                }
                $data = Utils::getMultipleAppSettings($settings);

                //if logo or other images are set append the base url to it
                $imageProperties = ['logo', 'whiteLogo', 'institutionLogo', 'loginBackground'];
                foreach ($imageProperties as $imageProperty) {
                    if (isset($data[$imageProperty])) {
                        $data[$imageProperty] = base_url() . $data[$imageProperty];
                    }
                }
                $data['recaptchaSiteKey'] = getenv('RECAPTCHA_PUBLIC_KEY');
                if (isset($data['portalHomeMenu'])) {
                    //set each image url relative to the base url
                    foreach ($data['portalHomeMenu'] as $key => $menu) {
                        if (isset($menu['image'])) {
                            $data['portalHomeMenu'][$key]['image'] = base_url() . $menu['image'];
                        }
                    }
                }
                //remove the following fields from the licenseTypes
                $fieldsToRemove = ['table', 'uniqueKeyField', 'selectionFields', 'onCreateValidation', 'onUpdateValidation', 'implicitRenewalFields', 'renewalTable', 'renewalJsonFields', 'fieldsToUpdateOnRenewal', 'searchFields'];
                if (isset($data['licenseTypes']) && is_array($data['licenseTypes'])) {
                    foreach ($data['licenseTypes'] as $key => $licenseType) {
                        foreach ($fieldsToRemove as $fieldToRemove) {
                            if (isset($licenseType[$fieldToRemove])) {
                                unset($data['licenseTypes'][$key][$fieldToRemove]);
                            }
                        }
                    }
                }

                return $this->respond($data, ResponseInterface::HTTP_OK);
            } catch (\Throwable $th) {
                log_message('error', $th);
                CacheHelper::delete('app_settings');
                return $this->respond(['message' => 'App settings file not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

        }, 3600); // Cache for 1 hour
    }



    public function verifyRecaptcha()
    {
        //get the recaptcha key from .env
        $key = getenv('RECAPTCHA_PRIVATE_KEY');
        $recaptcha = new ReCaptchaReCaptcha($key);
        $token = $this->request->getVar('g-recaptcha-response');
        $resp = $recaptcha->verify($token, $_SERVER['REMOTE_ADDR']);
        if ($resp->isSuccess()) {
            return $this->respond(['message' => 'Recaptcha verified'], ResponseInterface::HTTP_OK);
        } else {
            return $this->respond(['message' => 'Recaptcha not verified'], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "password_confirm"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirm", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Registration successful"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function register()
    {
        $rules = [
            "username" => "required|is_unique[users.username]",
            "password" => "required|min_length[8]|strong_password[]",
            "password_confirm" => "required|matches[password]",
            "email_address" => "required|valid_email|is_unique[auth_identities.secret]",
        ];
        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $userObject = new UsersModel();
        $userEntityObject = new User(
            [
                "username" => $this->request->getVar("username"),
                "password" => $this->request->getVar("password"),
                "email" => $this->request->getVar("email"),
            ]
        );
        $userObject->save($userEntityObject);
        $response = [
            "status" => true,
            "message" => "User saved successfully",
            "data" => []
        ];

        return $this->respondCreated($response);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *             @OA\Property(property="token", type="string"),
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Invalid credentials"
     *     )
     * )
     */
    // public function login()
    // {
    //     if (auth()->loggedIn()) {
    //         auth()->logout();
    //     }

    //     $rules = [
    //         "email" => "required|valid_email",
    //         "password" => "required"
    //     ];
    //     $key = getenv('AUTH_ENCRYPTION_KEY');

    //     // Check if 2FA code is required for this request
    //     $is2faVerification = $this->request->getVar('verification_mode') === '2fa';

    //     if ($is2faVerification) {
    //         // When verifying 2FA, we need the code and user ID
    //         $rules = [
    //             'user_id' => 'required|numeric',
    //             'code' => 'required|min_length[6]|max_length[6]|numeric'
    //         ];
    //     }

    //     if (!$this->validate($rules)) {
    //         $message = implode(" ", array_values($this->validator->getErrors()));
    //         return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
    //     }

    //     if ($is2faVerification) {
    //         // 2FA VERIFICATION FLOW
    //         $userId = $this->request->getVar('user_id');
    //         $code = $this->request->getVar('code');

    //         $userObject = new UsersModel();
    //         $userData = $userObject->findById($userId);

    //         if (!$userData) {
    //             return $this->respond(["message" => "User not found"], ResponseInterface::HTTP_NOT_FOUND);
    //         }

    //         // Verify Google Authenticator code
    //         $g = new GoogleAuthenticator();
    //         ;
    //         $secret = $userData->google_auth_secret;

    //         if (!$secret) {
    //             return $this->respond(["message" => "2FA not set up for this account"], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         if (!$g->verifyCode($secret, $code, 2)) {
    //             return $this->respond(["message" => "Invalid verification code"], ResponseInterface::HTTP_BAD_REQUEST);
    //         }

    //         // 2FA succeeded, log the user in
    //         auth()->login($userId);

    //         $token = $userData->generateAccessToken($key);

    //         $response = [
    //             "token" => $token->raw_token,
    //             "user" => $userData,
    //         ];

    //         return $this->respondCreated($response);
    //     }

    //     // STANDARD LOGIN FLOW (FIRST STEP)
    //     $credentials = [
    //         "email" => $this->request->getVar("email"),
    //         "password" => $this->request->getVar("password")
    //     ];

    //     $loginAttempt = auth()->attempt($credentials);
    //     if (!$loginAttempt->isOK()) {
    //         return $this->respond(["message" => "Wrong combination. Try again"], ResponseInterface::HTTP_NOT_FOUND);
    //     }

    //     $userObject = new UsersModel();
    //     $userData = $userObject->findById(auth("tokens")->user()->id);
    //     log_message('info', $userData);
    //     // Check if 2FA is enabled for this user
    //     if (!empty($userData->google_auth_secret)) {
    //         // Don't actually log them in yet - require 2FA verification
    //         auth()->logout();
    //         //generate a new random token
    //         $token = bin2hex(random_bytes(16));
    //         // Store the token in the session or database for later verification
    //         $userObject->update($userData->id, [
    //             '2fa_verification_token' => $token
    //         ]);
    //         return $this->respond([
    //             "message" => "2FA verification required",
    //             "requires_2fa" => true,
    //             "token" => $userData->id,
    //         ], ResponseInterface::HTTP_OK);
    //     }

    //     // No 2FA required, proceed with normal login
    //     $token = $userData->generateAccessToken($key);

    //     $response = [
    //         "token" => $token->raw_token,
    //         "user" => $userData,
    //     ];

    //     return $this->respondCreated($response);
    // }

    public function setupGoogleAuth()
    {
        $uuid = $this->request->getVar('uuid');
        $secrets = $this->prep2FaSetupForUser($uuid);

        $secret = $secrets['secret'];
        $qrCodeUrl = $secrets['qr_code_url'];

        return $this->respond([
            'secret' => $secret, // User can manually enter this if they can't scan QR
            'qr_code_url' => $qrCodeUrl,
            'message' => 'Scan this QR code with Google Authenticator app'
        ], ResponseInterface::HTTP_OK);
    }

    private function prep2FaSetupForUser(string $uuid)
    {
        try {
            $userObject = new UsersModel();
            /**
             * @var UsersModel
             */
            $userData = $userObject->where(["uuid" => $uuid])->first();

            // Create Google Authenticator object
            $authenticator = new GoogleAuthenticator();

            // Generate a secret key
            $secret = $authenticator->createSecret();

            // Create the QR code URL
            $appName = $userData->user_type === 'admin' ? getenv("GOOGLE_AUTHENTICATOR_APP_NAME") : getenv("GOOGLE_AUTHENTICATOR_APP_NAME") . " - Portal";
            $portalUrl = $userData->user_type === 'admin' ? getenv("ADMIN_PORTAL_URL") : getenv("PORTAL_URL");
            $email = $userData->email_address;
            $qrCodeUrl = $authenticator->getQRCodeUrl($email, $secret, $appName);

            // Save the secret key to the user's record in the database
            $userObject->update($userData->id, [
                'two_fa_setup_token' => $secret
            ]);
            $this->send2FaSetupEmail($email, $userData->display_name, $secret, $qrCodeUrl, $uuid, $portalUrl);
            return [
                'secret' => $secret, // User can manually enter this if they can't scan QR
                'qr_code_url' => $qrCodeUrl
            ];
        } catch (\Throwable $th) {
            log_message('error', $th);
            throw $th;
        }

    }

    /**
     * Send an email to the user with instructions on how to set up 2FA.
     * @param string $email The user's email address
     * @param string $displayName The user's display name
     * @param string $secret The secret for the authenticator
     * @param string $qrCodeUrl The URL of the QR code
     * @param string $uuid The user's UUID
     * @return void
     * @throws \Exception If the email template or subject for 2FA not found
     */
    private function send2FaSetupEmail($email, $displayName, $secret, $qrCodeUrl, $uuid, $portalUrl): void
    {
        try {
            $settings = service("settings");
            $messageTemplate = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_TEMPLATE);
            $subject = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_SUBJECT);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject for 2FA not found");
            }
            $templateEngine = new TemplateEngineHelper();

            //save the qr code url as a file and generate a link for it. gmail does not accept inline images
            $qrPath = "";//TODO; provide a link to an empty image
            try {
                $qrPath = Utils::generateQRCode($qrCodeUrl, true, $email . "_2fa_qr_code");
            } catch (\Throwable $th) {
                log_message('error', $th);
                $messageTemplate = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_SETUP_EMAIL_TEMPLATE_CODE_ONLY);
            }

            $message = $templateEngine->process($messageTemplate, ['qr_code_url' => $qrPath, 'secret' => $secret, 'display_name' => $displayName]);
            //add the portal link to the message
            $message .= "<p>Click here to continue the setup: <a href='" . $portalUrl . '/' . $uuid . "'>" . $portalUrl . "</a></p>";

            $emailConfig = new EmailConfig($message, $subject, $email);


            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            log_message('error', $th);
            throw $th;
        }


    }

    public function verifyAndEnableGoogleAuth()
    {
        $rules = [
            'token' => 'required|min_length[6]|max_length[6]|numeric',
            'uuid' => 'required|is_not_unique[users.uuid]',
        ];
        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $uuid = $this->request->getVar('uuid');
        $userObject = new UsersModel();
        $userData = $userObject->where(["uuid" => $uuid])->first();
        if (!$userData) {
            return $this->respond([
                'message' => 'No user found. Please start setup again.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $userId = $userData->id;
        $secret = $userData->two_fa_setup_token;



        // Get the temporary secret from session
        if (!$secret) {
            return $this->respond([
                'message' => 'No 2FA setup in progress. Please start setup again.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Verify the code
        $authenticator = new GoogleAuthenticator();
        $token = $this->request->getVar('token');

        if (!$authenticator->verifyCode($secret, $token, 2)) {
            return $this->respond([
                'message' => 'Invalid verification token'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
        // token is valid, save the secret to the user's record

        $userObject->update($userData->id, [
            'two_fa_setup_token' => null,
            'google_auth_secret' => $secret,
        ]);
        $this->send2FaVerificationEmail($userData->email, $userData->display_name);
        return $this->respond([
            'message' => '2FA has been successfully enabled for your account'
        ], ResponseInterface::HTTP_OK);
    }

    private function send2FaVerificationEmail($email, $displayName): void
    {
        try {
            $settings = service("settings");
            $messageTemplate = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_VERIFICATION_EMAIL_TEMPLATE);
            $subject = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_VERIFICATION_EMAIL_SUBJECT);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject for 2FA verification not found");
            }
            $templateEngine = new TemplateEngineHelper();
            $message = $templateEngine->process($messageTemplate, ['display_name' => $displayName]);


            $emailConfig = new EmailConfig($message, $subject, $email);
            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function disableGoogleAuth()
    {
        $userId = $this->request->getVar('user_id');
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);


        // Remove 2FA
        $userData->google_auth_secret = null;
        $userObject->save($userData);
        $this->send2FaDisabledEmail($userData->email, $userData->display_name);
        return $this->respond([
            'message' => '2FA has been successfully disabled for this account'
        ], ResponseInterface::HTTP_OK);
    }

    private function send2FaDisabledEmail($email, $displayName): void
    {
        try {
            $settings = service("settings");
            $messageTemplate = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_DISABLED_EMAIL_TEMPLATE);
            $subject = $settings->get(SETTING_2_FACTOR_AUTHENTICATION_DISABLED_EMAIL_SUBJECT);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject for 2FA disabled not found");
            }
            $templateEngine = new TemplateEngineHelper();
            $message = $templateEngine->process($messageTemplate, ['display_name' => $displayName]);

            $emailConfig = new EmailConfig($message, $subject, $email);
            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    /**
     * @OA\Get(
     *     path="/admin/profile",
     *     summary="Get user profile",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="User profile data",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", type="object")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function profile()
    {
        $userId = auth()->id();

        $userData = AuthHelper::getAuthUser($userId);
        //only return needed fields

        $excludeProfileDataFields = ['uuid', 'id', 'created_at', 'updated_at', 'deleted_at'];
        $data = [
            'display_name' => $userData->display_name,
            'email_address' => $userData->email_address,
            'user_type' => $userData->user_type,
            'region' => $userData->region,
            'role_name' => $userData->role_name
        ];
        if ($userData->profile_data) {
            $data['profile_data'] = array_diff_key((array) $userData->profile_data, array_flip($excludeProfileDataFields));
        }
        $permissionsList = AuthHelper::getAuthUserPermissions($userData);
        return $this->respond([
            "user" => $data,
            "permissions" => $permissionsList
        ], ResponseInterface::HTTP_OK);
    }

    public function portalDashboard()
    {
        ///get the portal dashboard data for a given user. this will be used for non-admin users
        $userId = auth()->id();
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);
        if (!$userData) {
            return $this->respond(["message" => "User not found"], ResponseInterface::HTTP_NOT_FOUND);
        }
        $permissionsList = [];
        //for admins use their roles to get permissions
        if ($userData->user_type === 'admin') {
            throw new \Exception("Admins are not allowed to use this endpoint");
        } else {
            //for non admins use their permissions from the app.settings.json file.
            //also get their profile details from their profile table
            $db = \Config\Database::connect();

            $profileData = $db->table($userData->profile_table)->where(["uuid" => $userData->profile_table_uuid])->get()->getFirstRow();
            if (!empty($profileData)) {

                $userData->profile_data = $profileData;
            }
        }
        $userData->permissions = $permissionsList;
        return $this->respond([
            "user" => $userData,
            "permissions" => $permissionsList
        ], ResponseInterface::HTTP_OK);
    }

    private function getUserDetails($userId)
    {
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);
        if (!$userData) {
            throw new \Exception("User not found");
        }
        $permissionsList = [];
        //for admins use their roles to get permissions
        if ($userData->user_type === 'admin') {
            $rpObject = new RolePermissionsModel();
            $permissions = $rpObject->where("role", $userData->role_name)->findAll();

            foreach ($permissions as $permission) {
                $permissionsList[] = $permission['permission'];
            }
        } else {
            //for non admins use their permissions from the app.settings.json file.
            //also get their profile details from their profile table
            $db = \Config\Database::connect();

            $profileData = $db->table($userData->profile_table)->where(["uuid" => $userData->profile_table_uuid])->get()->getFirstRow();
            if (!empty($profileData)) {
                //if images are stored in the profile table, get them
                $userData->profile_data = $profileData;
            }
        }
        $userData->permissions = $permissionsList;
        return $userData;
    }

    /**
     * @OA\Get(
     *     path="/admin/logout",
     *     summary="User logout",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Logout successful"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function logout()
    {
        auth()->logout();
        auth()->user()->revokeAllAccessTokens();
        return $this->respondCreated([
            "status" => true,
            "message" => "logged out",
            "data" => []
        ]);
    }

    /**
     * if user tries to access a page when not logged in
     */
    public function accessDenied()
    {
        return $this->respond(['message' => "You're not logged in"], ResponseInterface::HTTP_UNAUTHORIZED);
    }


    public function mobileLogin()
    {
        try {
            // Check if 2FA code is required for this request
            $is2faVerification = $this->request->getVar('verification_mode') === '2fa';

            // Validate credentials
            $rules = setting('Validation.login') ?? [
                'username' => [
                    'label' => 'Auth.username',
                    'rules' => 'required|string|min_length[3]|max_length[50]',
                ],
                'password' => [
                    'label' => 'Auth.password',
                    'rules' => 'required',
                ],
                'device_name' => [
                    'label' => 'Device Name',
                    'rules' => "required|string|in_list[admin portal,practitioners portal]",
                    'errors' => [
                        'in_list' => 'Invalid request',
                    ],
                ],
                'user_type' => [
                    'label' => 'User Type',
                    'rules' => 'required|string',
                ],
            ];

            if ($is2faVerification) {
                // When verifying 2FA, we need the code and token
                $rules = [
                    'token' => 'required',
                    'code' => 'required|min_length[6]|max_length[6]|numeric',
                    'device_name' => [
                        'label' => 'Device Name',
                        'rules' => "required|string|in_list[admin portal,practitioners portal]",
                        'errors' => [
                            'in_list' => 'Invalid request'
                        ],
                    ],
                ];
            }
            if (!$this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
                log_message('error', print_r($this->validator->getErrors(), true));
                return $this->response
                    ->setJSON(['errors' => $this->validator->getErrors()])
                    ->setStatusCode(401);
            }

            // Make sure the user_type is a valid one
            $userType = $this->request->getVar('user_type');
            $deviceName = $this->request->getVar('device_name');
            if (!in_array($userType, USER_TYPES)) {
                return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if ($is2faVerification) {
                // 2FA VERIFICATION FLOW
                $token = $this->request->getVar('token');
                $code = $this->request->getVar('code');

                $userObject = new UsersModel();
                $userData = $userObject->where(["two_fa_verification_token" => $token])->first();

                if (!$userData) {
                    return $this->respond(["message" => "User not found"], ResponseInterface::HTTP_NOT_FOUND);
                }

                // Verify Google Authenticator code
                $authenticator = new GoogleAuthenticator();
                $secret = $userData->google_auth_secret;

                if (!$secret) {
                    return $this->respond(["message" => "2FA not set up for this account"], ResponseInterface::HTTP_BAD_REQUEST);
                }

                if (!$authenticator->verifyCode($secret, $code, 2)) {
                    return $this->respond(["message" => "Invalid verification code"], ResponseInterface::HTTP_BAD_REQUEST);
                }

                // 2FA succeeded, log the user in
                auth()->login($userData);
                $userObject->update($userData->id, [
                    'two_fa_verification_token' => null
                ]);
            } else {
                // Get the credentials for login - now using username instead of email
                $credentials = [
                    'username' => $this->request->getPost('username'),
                    'password' => $this->request->getPost('password')
                ];

                // Get email and password
                $username = $credentials['username'];
                // Check if the user is the correct type
                $userObject = new UsersModel();
                $user = $userObject->findByCredentials(['username' => $username]);

                if (!$user || !auth()->check($credentials)) {
                    return $this->respond(['message' => 'Wrong combination. Try again'], ResponseInterface::HTTP_NOT_FOUND);

                }

                // Attempt to login
                $result = auth()->attempt($credentials);
                if (!$result->isOK()) {
                    return $this->response
                        ->setJSON(['message' => 'Wrong combination. Try again'])
                        ->setStatusCode(401);
                }


                $userData = $userObject->findById(auth()->id());
                if ($userData->user_type !== $userType) {
                    return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
                }

                // If the device name is admin portal, check if the user is an admin
                if ($deviceName === 'admin portal' && $userData->user_type !== 'admin') {
                    return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
                }

                // If the device name is practitioners portal, make sure the user is not an admin
                if ($deviceName === 'practitioners portal' && $userData->user_type === 'admin') {
                    return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
                }

                // Check if the user's two_fa_deadline is set and if it is in the past and has not set up 2FA
                if ($userData->two_fa_deadline && $userData->two_fa_deadline < date('Y-m-d') && empty($userData->google_auth_secret)) {
                    // Set up 2FA. send the email to the user
                    $message = 'The deadline to enable 2 factor authentication has passed. The instructions to enable it have been sent to your email. Please check your email.';
                    try {
                        $this->prep2FaSetupForUser($userData->uuid);
                    } catch (\Throwable $th) {

                        log_message('error', "Error sending 2FA setup email: " . $th);
                        $message = 'The deadline to enable 2 factor authentication has passed. We are unable to send the instructions to enable it to your email at this moment. Please try again in a few minutes or contact support if the problem persists.';
                    }

                    return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
                }

                // Check if 2FA is enabled for this user
                if (!empty($userData->google_auth_secret)) {
                    // Don't actually log them in yet - require 2FA verification
                    auth()->logout();
                    // Generate a new random token
                    $token = bin2hex(random_bytes(16));
                    // Store the token in the session or database for later verification
                    $userObject->update($userData->id, [
                        'two_fa_verification_token' => $token
                    ]);
                    return $this->respond([
                        "message" => "2FA verification required",
                        "requires_2fa" => true,
                        "token" => $token,
                    ], ResponseInterface::HTTP_OK);
                }
            }

            // Generate token and return to client
            $token = auth()->user()->generateAccessToken(service('request')->getVar('device_name'));

            return $this->response
                ->setJSON(['token' => $token->raw_token]);
        } catch (\Throwable $th) {
            log_message('error', "Error logging in: " . $th);
            return $this->respond(['message' => 'Error logging in'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function sendResetToken()
    {
        // Validation rules
        $rules = [
            'username' => 'required|min_length[3]|max_length[100]'
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $username = $this->request->getVar('username');
        $ipAddress = $this->request->getIPAddress();
        $userAgent = $this->request->getUserAgent()->getAgentString();

        $settings = service("settings");
        $timeoutSettingValue = $settings->get(SETTING_PASSWORD_RESET_TOKEN_TIMEOUT);
        // Set expiration
        $timeout = $timeoutSettingValue ? (int) $timeoutSettingValue : 15;
        $expiresAt = Time::now()->addMinutes($timeout);

        // Check rate limiting (max 5 attempts per email per hour)
        if (!$this->checkRateLimit($username, $ipAddress)) {
            $this->logResetAttempt($username, $ipAddress, $userAgent, false);
            return $this->respond([
                'message' => 'Too many reset attempts. Please try again later.'
            ], ResponseInterface::HTTP_TOO_MANY_REQUESTS);
        }

        // Find user by username 
        $user = $this->userModel->where('username', $username)
            ->first();

        // Always return success message for security (don't reveal if user exists)
        $successMessage = 'If an account with that username exists, a password reset link has been sent to the associated email address.';
        if ($user === null) {
            log_message('info', "$username not found");

            $this->logResetAttempt($username, $ipAddress, $userAgent, false);
            return $this->respond(['message' => $successMessage, 'data' => ['timeout' => $timeout]], ResponseInterface::HTTP_OK);
        }

        try {
            // Invalidate any existing tokens for this user
            $this->passwordResetTokenModel->where('user_id', $user->id)
                ->where('used_at', null)
                ->where('expires_at >', Time::now())
                ->set(['used_at' => Time::now()])
                ->update();

            // Generate secure token
            $token = Utils::generateSecure6DigitToken();
            $tokenHash = password_hash($token, PASSWORD_ARGON2ID);


            // Save token to database
            $tokenData = [
                'user_id' => $user->id,
                'token' => $token,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => Time::now(),
                'updated_at' => Time::now()
            ];

            $this->passwordResetTokenModel->insert($tokenData);

            // Send reset email
            $this->sendResetEmail($user->email_address, $user->display_name, $token, $timeout);

            // Log successful attempt
            $this->logResetAttempt($user->email_address, $ipAddress, $userAgent, true);

            return $this->respond(['message' => $successMessage, 'data' => ['timeout' => $timeout]], ResponseInterface::HTTP_OK);

        } catch (\Exception $e) {
            log_message('error', 'Password reset error: ' . $e);
            $this->logResetAttempt($username, $ipAddress, $userAgent, false);

            return $this->respond([
                'message' => 'An error occurred while processing your request. Please try again later.'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Reset password with 6-digit token
     */
    public function resetPassword()
    {
        //TODO: password strength check
        $rules = [
            'token' => 'required|exact_length[6]|numeric',
            'username' => 'required|min_length[3]|max_length[100]',
            "password" => "required|min_length[8]",
            "password_confirm" => "required|matches[password]"
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }


        $token = $this->request->getVar('token');
        $password = $this->request->getVar('password');
        $ipAddress = $this->request->getIPAddress();


        // Find and verify token
        $tokenBuilder = $this->passwordResetTokenModel->builder();
        $tokenBuilder->where('token', $token)
            ->where('used_at', null)
            ->where('expires_at >', Time::now());
        $tokenRecord = $tokenBuilder->get()->getRow();


        if (!$tokenRecord) {
            return $this->respond([
                'message' => 'Invalid or expired token. Please request a new one.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Verify token hash
        if (!password_verify($token, $tokenRecord->token_hash)) {
            return $this->respond([
                'message' => 'Invalid token.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Get user
        $userObject = auth()->getProvider();

        $user = $userObject->find($tokenRecord->user_id);
        if (!$user || strtolower($user->username) !== strtolower($this->request->getVar('username'))) {
            return $this->respond([
                'message' => 'User not found.'
            ], ResponseInterface::HTTP_NOT_FOUND);
        }
        try {
            $user->fill([
                'email' => $user->email_address,
                'password' => $password
            ]);
            $userObject->save($user);
            // $user->password = $password;
            // $userObject->save($user);

            // Mark token as used
            $this->passwordResetTokenModel->delete($tokenRecord->id);

            // TODO: Add to password history
            // $this->addToPasswordHistory($user->id, $hashedPassword);
            try {
                // Optionally: Send confirmation email
                $this->sendPasswordChangeConfirmationEmail($user->email, $user->display_name);

            } catch (\Throwable $th) {
                log_message('error', 'Error sending password change confirmation email: ' . $th);
            }

            return $this->respond([
                'message' => 'Password reset successfully. Please login with your new credentials.',
                'success' => true
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $e) {
            log_message('error', 'Password reset completion error: ' . $e->getMessage());

            return $this->respond([
                'message' => 'An error occurred while resetting your password. Please try again.'
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function sendPasswordChangeConfirmationEmail($email, $displayName): void
    {
        try {
            $settings = service("settings");
            $messageTemplate = $settings->get(SETTING_RESET_PASSWORD_CONFIRMATION_EMAIL_TEMPLATE);
            $subject = $settings->get(SETTING_RESET_PASSWORD_CONFIRMATION_EMAIL_SUBJECT);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject for password reset confirmation not found");
            }
            $templateEngine = new TemplateEngineHelper();
            $message = $templateEngine->process($messageTemplate, ['display_name' => $displayName]);


            $emailConfig = new EmailConfig($message, $subject, $email);


            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Verify 6-digit token
     */
    public function verifyResetToken()
    {
        $rules = [
            'token' => 'required|exact_length[6]|numeric'
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $token = $this->request->getPost('token');
        $ipAddress = $this->request->getIPAddress();

        // Find valid token
        $tokenRecord = $this->passwordResetTokenModel
            ->where('token', $token)
            ->where('used_at', null)
            ->where('expires_at >', Time::now())
            ->first();

        if (!$tokenRecord) {
            return $this->respond([
                'message' => 'Invalid or expired token. Please request a new one.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        // Verify token hash
        if (!password_verify($token, $tokenRecord->token_hash)) {
            return $this->respond([
                'message' => 'Invalid token.'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond([
            'message' => 'Token verified successfully.',
            'success' => true
        ], ResponseInterface::HTTP_OK);
    }

    /**
     * Check if user has exceeded rate limit
     */
    private function checkRateLimit($identifier, $ipAddress): bool
    {
        $oneHourAgo = Time::now()->subHours(1);

        // Check attempts by email/username
        $emailAttempts = $this->passwordResetAttemptModel
            ->where('email', $identifier)
            ->where('created_at >', $oneHourAgo)
            ->countAllResults();

        // Check attempts by IP
        $ipAttempts = $this->passwordResetAttemptModel
            ->where('ip_address', $ipAddress)
            ->where('created_at >', $oneHourAgo)
            ->countAllResults();

        // Allow max 5 attempts per email and 10 per IP per hour
        return $emailAttempts < 5 && $ipAttempts < 10;
    }

    /**
     * Log password reset attempt
     */
    private function logResetAttempt($email, $ipAddress, $userAgent, $success): void
    {
        $this->passwordResetAttemptModel->insert([
            'email' => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'success' => $success ? 1 : 0,
            'created_at' => Time::now()
        ]);
    }



    /**
     * Send password reset email
     */
    private function sendResetEmail($email, $displayName, $token, $timeout): void
    {
        try {
            $settings = service("settings");
            $messageTemplate = $settings->get(SETTING_RESET_PASSWORD_EMAIL_TEMPLATE);
            $subject = $settings->get(SETTING_RESET_PASSWORD_EMAIL_SUBJECT);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject not found");
            }
            $templateEngine = new TemplateEngineHelper();
            $message = $templateEngine->process($messageTemplate, ['token' => $token, 'display_name' => $displayName, 'timeout' => $timeout]);


            $emailConfig = new EmailConfig($message, $subject, $email);


            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Clean up expired tokens (call this periodically)
     */
    public function cleanupExpiredTokens(): void
    {
        $this->passwordResetTokenModel
            ->where('expires_at <', Time::now())
            ->delete();

        // Clean up old attempts (keep for 30 days)
        $thirtyDaysAgo = Time::now()->subDays(30);
        $this->passwordResetAttemptModel
            ->where('created_at <', $thirtyDaysAgo)
            ->delete();
    }

    public function practitionerLogin()
    {
        // Validate credentials
        $rules = [
            'username' => 'required',
            'password' => 'required',
            'type' => 'required|string',
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $credentials = [
            'email' => $this->request->getPost('username'),
            'password' => $this->request->getPost('password'),
        ];
        $result = auth()->attempt($credentials);
        if (!$result->isOK()) {
            return $this->response
                ->setJSON(['message' => $result->reason()])
                ->setStatusCode(401);
        }

        // Generate token and return to client
        $token = auth()->user()->generateAccessToken(service('request')->getVar('device_name'));

        return $this->response
            ->setJSON(['token' => $token->raw_token]);
    }

    /**
     * @OA\Post(
     *     path="/admin/roles",
     *     summary="Create new role",
     *     tags={"User Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"role_name"},
     *             @OA\Property(property="role_name", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="login_destination", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Role created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function createRole()
    {
        $rules = [
            "role_name" => "required|is_unique[roles.role_name]"
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new RolesModel();
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $id = $model->getInsertID();
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Role created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function updateRole($role_id)
    {
        $rules = [
            "role_name" => "permit_empty|is_unique[roles.role_name,role_id,$role_id]"
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        //restore it if it had been deleted
        $data->deleted_at = null;
        $data->role_id = $role_id;
        $model = new RolesModel();
        if (!$model->update($role_id, $data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Role updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deleteRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->delete($role_id)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Role deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restoreRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->update($role_id, (object) ['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Role restored successfully'], ResponseInterface::HTTP_OK);
    }

    public function getRole($role_id)
    {
        $model = new RolesModel();
        $data = $model->find($role_id);
        if (!$data) {
            return $this->respond(["message" => "Role not found"], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $permissionsModel = new PermissionsModel();
        $permissions = $permissionsModel->getRolePermissions($data['role_name']);
        $excludedPermissions = $permissionsModel->getRoleExcludedPermissions($data['role_name']);
        return $this->respond(['data' => $data, 'permissions' => $permissions, 'excludedPermissions' => $excludedPermissions], ResponseInterface::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/admin/roles",
     *     summary="Get all roles",
     *     tags={"User Management"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of roles",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function getRoles()
    {
        $cacheKey = Utils::generateHashedCacheKey("get_roles", (array) $this->request->getVar());
        return CacheHelper::remember($cacheKey, function () {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
            $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $model = new RolesModel();
            $builder = $param ? $model->search($param) : $model->builder();
            $builder->join('users', "roles.role_name = users.role_name", "left")
                ->select("roles.*, count(users.id) as number_of_users")
                ->groupBy('roles.role_name');
            if ($withDeleted) {
                $model->withDeleted();
            }
            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();

            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        });
    }

    /**
     * @api {post} /rolePermission add a permission to a role
     * @apiName addRolePermission
     * @apiGroup RolePermission
     *
     * @apiSuccess {String} Permission added to Role successfully.
     */
    public function addRolePermission()
    {
        $rules = [
            "role" => "required|is_not_unique[roles.role_name]",
            "permission" => "required|is_not_unique[permissions.name]"
        ];

        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new RolePermissionsModel();
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Permission added to role successfully'], ResponseInterface::HTTP_OK);
    }

    /**
     * @api {delete} /rolePermission/:id Delete a permission from a role
     * @apiName DeleteRolePermission
     * @apiGroup RolePermission
     *
     * @apiParam {Number} id The id of that role permission from the database.
     *
     * @apiSuccess {String} message Role deleted successfully.
     */
    public function deleteRolePermission($role, $permission)
    {


        $model = new RolePermissionsModel();
        if (!$model->where("role", $role)->where("permission", $permission)->delete(null, true)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $this->invalidateCache('get_roles');
        return $this->respond(['message' => 'Permission deleted from role successfully'], ResponseInterface::HTTP_OK);
    }



    /**
     * @OA\Post(
     *     path="/admin/users",
     *     summary="Create new user",
     *     tags={"User Management"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password", "role_id"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="role_id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User created successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function createUser()
    {
        $userTypes = implode(",", USER_TYPES);
        $rules = [
            "username" => "required|is_unique[users.username]",
            "email_address" => "required|valid_email|is_unique[auth_identities.secret]",
            "phone" => "required|min_length[10]",
            "role_name" => "required|is_not_unique[roles.role_name]",
            "password" => "required|min_length[8]|strong_password[]",
            "password_confirm" => "required|matches[password]",
            "display_name" => "required",
            "user_type" => "required|in_list[$userTypes]",
        ];
        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $userObject = auth()->getProvider();
        $data = $this->request->getVar();
        $data['email'] = $data['email_address'];
        $userEntityObject = new User(
            $data
        );
        $userObject->save($userEntityObject);
        try {
            try {
                $this->sendNewUserEmail($userEntityObject->email_address, $userEntityObject->display_name, $userEntityObject->username, $userEntityObject->user_type);
            } catch (\Throwable $th) {
                log_message('error', "Error triggering user added event for user id: " . $userEntityObject->email_address);
                log_message('error', $th);
            }
        } catch (\Throwable $th) {
            log_message('error', "Error triggering user added event for user id: " . $userEntityObject->email_address);
            log_message('error', $th);
        }

        $id = $userObject->getInsertID();
        return $this->respond(['message' => 'User created successfully', 'data' => $id], ResponseInterface::HTTP_OK);
    }

    public function createNonAdminUsers()
    {
        try {
            $userTypesArray = USER_TYPES;
            // Remove 'admin' from the array
            if (($key = array_search('admin', $userTypesArray)) !== false) {
                unset($userTypesArray[$key]);
            }
            $userTypes = implode(",", $userTypesArray);

            // Base rules without password requirement
            $baseRules = [
                "username" => "required|is_unique[users.username]|is_unique[auth_identities.secret]",
                "email_address" => "required|valid_email|is_unique[auth_identities.secret]",
                "phone" => "required|min_length[10]",
                "display_name" => "permit_empty",//for some users it will be taken from their profile
                "user_type" => "required|in_list[$userTypes]",
            ];

            // Password rules 
            $passwordRules = [
                "password" => "min_length[8]|strong_password[]",
                "password_confirm" => "matches[password]",
            ];

            // licenses rules
            $licensesRules = [
                "profile_table" => "in_list[licenses]",
                "profile_table_uuid" => "is_not_unique[licenses.uuid]",
            ];

            //TODO: cpd and other user types would have their rules here
            // Get the JSON data
            $usersData = $this->request->getJSON(true);

            // Check if it's a single user (object) or multiple users (array of objects)
            if (!isset($usersData[0])) {
                // Convert single user to array format
                $usersData = [$usersData];
            }

            $results = [];
            $userObject = auth()->getProvider();

            foreach ($usersData as $userData) {
                // Create validation for this specific user
                $validator = \Config\Services::validation();

                // Validate base rules
                $validator->setRules($baseRules);

                // Only apply password rules if password is provided
                if (!empty($userData['password'])) {
                    $validator->setRules($passwordRules);
                }
                if ($userData['user_type'] === 'license') {
                    $validator->setRules($licensesRules);
                }

                if (!$validator->run($userData)) {
                    $message = implode(" ", array_values($validator->getErrors()));
                    $results[] = [
                        'status' => 'error',
                        'message' => $message,
                        'data' => $userData['username'] ?? 'Unknown user'
                    ];
                    continue;
                }
                $userData['email_address'] = $userData['email'];
                try {
                    $userEntityObject = new User($userData);

                    $userObject->save($userEntityObject);
                    $id = $userObject->getInsertID();
                    try {
                        $this->sendNewUserEmail($userEntityObject->email_address, $userEntityObject->display_name, $userEntityObject->username, $userEntityObject->user_type);
                    } catch (\Throwable $th) {
                        log_message('error', "Error triggering user added event for user id: " . $userEntityObject->email_address);
                        log_message('error', $th);
                    }

                    $results[] = [
                        'status' => 'success',
                        'message' => 'User created successfully',
                        'data' => $userData['username']
                    ];
                } catch (\CodeIgniter\Database\Exceptions\DatabaseException $e) {
                    log_message('error', $e);
                    $results[] = [
                        'status' => 'error',
                        'message' => Utils::parseMysqlExceptions($e->getMessage()),
                        'data' => $userData['username'] ?? 'Unknown user'
                    ];
                } catch (\Exception $e) {
                    log_message('error', $e);
                    $results[] = [
                        'status' => 'error',
                        'message' => 'An error occurred. Please make sure the data is valid and is not a duplicate operation, and try again. ',
                        'data' => $userData['username'] ?? 'Unknown user'
                    ];
                }
            }


            return $this->respond(['message' => 'Users processed', 'details' => $results], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    private function sendNewUserEmail($email, $displayName, $username, $userType): void
    {
        try {
            $settings = service("settings");
            $userTypeMessagesMap = [
                "admin" => ["template" => SETTING_USER_ADMIN_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_ADMIN_ADDED_EMAIL_SUBJECT],
                "cpd" => ["template" => SETTING_USER_CPD_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_CPD_ADDED_EMAIL_SUBJECT],
                "license" => ["template" => SETTING_USER_LICENSE_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_LICENSE_ADDED_EMAIL_SUBJECT],
                "student" => ["template" => SETTING_USER_STUDENT_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_STUDENT_ADDED_EMAIL_SUBJECT],
                "exam_candidate" => ["template" => SETTING_USER_EXAM_CANDIDATE_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_EXAM_CANDIDATE_ADDED_EMAIL_SUBJECT],
                "housemanship_facility" => ["template" => SETTING_USER_HOUSEMANSHIP_FACILITY_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_HOUSEMANSHIP_FACILITY_ADDED_EMAIL_SUBJECT],
                "guest" => ["template" => SETTING_USER_GUEST_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_GUEST_ADDED_EMAIL_SUBJECT],
            ];
            $userMessageType = $userTypeMessagesMap[$userType] ?? null;
            if (empty($userMessageType)) {
                throw new \Exception("Email template or subject not set");
            }
            $messageTemplate = $settings->get($userMessageType['template']);
            $subject = $settings->get($userMessageType['template']);
            if (empty($messageTemplate) || empty($subject)) {
                throw new \Exception("Email template or subject not found");
            }
            $templateEngine = new TemplateEngineHelper();
            $message = $templateEngine->process($messageTemplate, ['display_name' => $displayName, 'username' => $username]);


            $emailConfig = new EmailConfig($message, $subject, $email);


            EmailHelper::sendEmail(emailConfig: $emailConfig);
        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function updateUser($userId)
    {
        try {
            // Get the existing user
            $userObject = auth()->getProvider();
            $existingUser = $userObject->find($userId);

            if ($existingUser === null) {
                return $this->respond(['error' => 'User not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Validate the request data
            $rules = [
                "username" => "permit_empty|is_unique[users.username,id,$userId]",
                "email_address" => "permit_empty|valid_email|is_unique[auth_identities.secret,user_id,$userId]",
                "phone" => "permit_empty|min_length[10]",
                "role_name" => "permit_empty|is_not_unique[roles.role_name]",
                "password" => "permit_empty|min_length[8]|strong_password[]",
                "password_confirm" => "permit_empty|matches[password]",
            ];

            if (!$this->validate($rules)) {
                $message = implode(" ", array_values($this->validator->getErrors()));
                return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Update the user details
            $data = $this->request->getVar();
            foreach ($data as $key => $value) {
                if ($value !== null && $value !== '') {
                    $existingUser->{$key} = $value;
                }
            }
            $existingUser->email = $data->email_address ?? $existingUser->email;
            $userObject->save($existingUser);

            return $this->respond(['message' => 'User updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function deleteUser($userId)
    {
        $model = new UsersModel();
        if (!$model->delete($userId)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        return $this->respond(['message' => 'User deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function getUser($userId)
    {
        $model = new UsersModel();
        $data = $model->find($userId);
        if (!$data) {
            return $this->respond("User not found", ResponseInterface::HTTP_BAD_REQUEST);
        }

        return $this->respond(['data' => $data,], ResponseInterface::HTTP_OK);
    }

    /**
     * @OA\Get(
     *     path="/admin/users",
     *     summary="Get all users",
     *     tags={"User Management"},
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Page number",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of users",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="total", type="integer")
     *         )
     *     ),
     *     security={{"bearerAuth": {}}}
     * )
     */
    public function getUsers()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $param = $this->request->getVar('param');
            $sortBy = $this->request->getVar('sortBy') ?? "id";
            $sortOrder = $this->request->getVar('sortOrder') ?? "asc";
            $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;


            $model = new UsersModel();
            $builder = $param ? $model->search($param) : $model->builder();

            $builder->select("id, uuid, display_name, user_type, username, email_address, status, status_message, active, created_at, region, position, picture, phone, role_name, CASE WHEN google_auth_secret IS NOT NULL THEN 'yes' ELSE 'no' END AS google_authenticator_setup")
            ;
            $filterArray = $model->createArrayFromAllowedFields($this->request->getVar());

            // Apply other filters
            foreach ($filterArray as $key => $value) {
                $value = Utils::parseParam($value);
                $builder = Utils::parseWhereClause($builder, $key, $value);

            }

            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();

            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns(),
                'columnFilters' => $model->getDisplayColumnFilters(),
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function banUser($userId)
    {
        try {
            $userObject = new UsersModel();
            $reason = $this->request->getVar('reason') ?? '';
            /** @var UsersModel $user */
            $user = $userObject->find($userId);
            if (!$user) {
                return $this->respond(['message' => 'User not found'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $user->ban($reason);
            return $this->respond(['message' => 'User banned successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function unbanUser($userId)
    {
        try {
            $userObject = new UsersModel();
            /** @var UsersModel $user */
            $user = $userObject->find($userId);
            if (!$user) {
                return $this->respond(['message' => 'User not found'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $user->unBan();
            return $this->respond(['message' => 'User unbanned successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function migrate()
    {
        $config = config('Migrations');
        $migration = new MigrationRunner(
            $config
        );


        try {
            // echo print_r($migration->findMigrations(), true);
            $migration->latest();
            echo "Migrations run successfully.";
        } catch (\Exception $e) {
            echo "Error running migrations: " . $e;
        }
    }

    public function runShieldMigration()
    {
        // Define the command to run the migration
        $command = 'php spark migrate -n CodeIgniter\Shield';

        // Execute the command
        exec($command, $output, $return_var);

        // Check if the command was successful
        if ($return_var === 0) {
            // Migration was successful
            echo "migration was successful";
        } else {
            // Migration failed
            echo "not successful";
        }
    }

    public function createApiKey()
    {
        // $userObject = auth()->getProvider();
        // $token =  $userObject->generateHmacToken(service('request')->getVar('token_name'));
        // log_message('debug',     config('Encryption')->key);
        $userObject = new UsersModel();
        $userData = $userObject->findById(auth()->id());
        $token = $userData->generateHmacToken(service('request')->getVar('token_name'));
        // $token = auth()->user()->generateHmacToken(service('request')->getVar('token_name'));
        return json_encode(['key' => $token->secret, 'secretKey' => $token->rawSecretKey]);
    }


    public function sqlQuery()
    {
        $fields = [
            "first_name",
            "middle_name",
            "last_name",
            "email",
            "intern_code",
            "sex",
            "registration_date",
            "nationality",
            "postal_address",
            "residential_address",
            "residential_city",
            "picture",
            "status",
            "residential_region",
            "criminal_offense",
            "training_institution",
            "date_of_graduation",
            "qualification",
            "date_of_birth",
            "mailing_city",
            "phone",
            "place_of_birth",
            "mailing_region",
            "crime_details",
            "referee1_name",
            "referee1_phone",
            "referee1_email",
            "referee2_name",
            "referee2_phone",
            "referee2_email",
            "referee1_letter_attachment",
            "referee2_letter_attachment",
            "certificate",
            "category",
            "type"
        ];

        foreach ($fields as $value) {
            echo "\"$value\", `$value`,";
        }
    }

    public function getUserTypes()
    {
        try {
            $userTypesArray = USER_TYPES;
            $result = array_map(function ($type) {
                return [
                    'value' => $type,
                    'key' => ucfirst($type)
                ];
            }, $userTypesArray);
            return $this->respond([
                'data' => $result,
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getPortalUserTypes()
    {
        try {
            $userTypesArray = Utils::getAppSettings("userTypesNames");

            return $this->respond([
                'data' => $userTypesArray,
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }
}
