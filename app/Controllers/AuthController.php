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
use App\Models\GuestsModel;
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

    public function appSettings()
    {
        // Determine authentication state first
        $isLoggedIn = auth("tokens")->loggedIn();
        $userId = auth("tokens")->id();

        // Cache key must reflect the auth state to prevent serving wrong content
        $cacheKey = $isLoggedIn && $userId
            ? "app_settings_authenticated_{$userId}"
            : "app_settings_guest";

        return CacheHelper::remember($cacheKey, function () use ($isLoggedIn) {
            //read the data from app-settings.json at the root of the project
            try {
                $settings = ['appName', 'appVersion', 'appLongName', 'logo', 'whiteLogo', 'loginBackground'];
                //if the user is logged in, add more settings
                if ($isLoggedIn) {
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
                // Don't cache errors - invalidate any existing cache
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

        // Invalidate user caches
        $this->invalidateCache('auth_users_');

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

        // Invalidate user caches
        $this->invalidateCache('auth_users_');

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
        $cacheKey = Utils::generateHashedCacheKey('auth_profile_', ['userId' => $userId]);

        return CacheHelper::remember($cacheKey, function () use ($userId) {
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
        }, 300); // Cache for 5 minutes only (sensitive data)
    }

    public function portalDashboard()
    {
        ///get the portal dashboard data for a given user. this will be used for non-admin users
        $userId = auth()->id();
        $cacheKey = Utils::generateHashedCacheKey('auth_portal_dashboard_', ['userId' => $userId]);

        return CacheHelper::remember($cacheKey, function () use ($userId) {
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
        }, 300); // Cache for 5 minutes only (sensitive data)
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
        $userId = auth()->id();
        auth()->logout();
        auth()->user()->revokeAllAccessTokens();

        // Invalidate user-specific caches
        $this->invalidateCache('auth_profile_');
        $this->invalidateCache('auth_portal_dashboard_');
        $this->invalidateCache('app_settings_authenticated_');

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
        $userModel = new UsersModel();
        $user = $userModel->where('username', $username)->first();

        // Always return success message for security (don't reveal if user exists)
        $successMessage = 'If an account with that username exists, a password reset link has been sent to the associated email address.';
        if ($user === null) {
            log_message('info', "$username not found");

            $this->logResetAttempt($username, $ipAddress, $userAgent, false);
            return $this->respond(['message' => $successMessage, 'data' => ['timeout' => $timeout]], ResponseInterface::HTTP_OK);
        }

        try {
            // Invalidate any existing tokens for this user
            $passwordResetTokenModel = new PasswordResetTokenModel();
            $passwordResetTokenModel->where('user_id', $user->id)
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

            $passwordResetTokenModel->insert($tokenData);

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
        $passwordResetTokenModel = new PasswordResetTokenModel();
        $tokenBuilder = $passwordResetTokenModel->builder();
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
            $passwordResetTokenModel->delete($tokenRecord->id);

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
        $passwordResetTokenModel = new PasswordResetTokenModel();
        $tokenRecord = $passwordResetTokenModel
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
        $passwordResetAttemptModel = new PasswordResetAttemptModel();

        // Check attempts by email/username
        $emailAttempts = $passwordResetAttemptModel
            ->where('email', $identifier)
            ->where('created_at >', $oneHourAgo)
            ->countAllResults();

        // Check attempts by IP
        $ipAttempts = $passwordResetAttemptModel
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
        $passwordResetAttemptModel = new PasswordResetAttemptModel();
        $passwordResetAttemptModel->insert([
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
        $cacheKey = Utils::generateHashedCacheKey('get_role_', ['role_id' => $role_id]);
        return CacheHelper::remember($cacheKey, function () use ($role_id) {
            $model = new RolesModel();
            $data = $model->find($role_id);
            if (!$data) {
                return $this->respond(["message" => "Role not found"], ResponseInterface::HTTP_BAD_REQUEST);
            }
            $permissionsModel = new PermissionsModel();
            $permissions = $permissionsModel->getRolePermissions($data['role_name']);
            $excludedPermissions = $permissionsModel->getRoleExcludedPermissions($data['role_name']);
            return $this->respond(['data' => $data, 'permissions' => $permissions, 'excludedPermissions' => $excludedPermissions], ResponseInterface::HTTP_OK);
        }, 600); // Cache for 10 minutes
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
        $this->invalidateCache('get_role');
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
        $this->invalidateCache('get_role');
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

        // Invalidate user caches
        $this->invalidateCache('auth_users_');

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
                "training_institution" => ["template" => SETTING_USER_TRAINING_INSTITUTION_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_TRAINING_INSTITUTION_ADDED_EMAIL_SUBJECT],
                "guest" => ["template" => SETTING_USER_GUEST_ADDED_EMAIL_TEMPLATE, "subject" => SETTING_USER_GUEST_ADDED_EMAIL_SUBJECT],
            ];
            $userMessageType = $userTypeMessagesMap[$userType] ?? null;
            if (empty($userMessageType)) {
                throw new \Exception("Email template or subject not set");
            }
            $messageTemplate = $settings->get($userMessageType['template']);
            $subject = $settings->get($userMessageType['subject']);
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
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
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

            // Invalidate user caches
            $this->invalidateCache('auth_users_');
            $this->invalidateCache('auth_profile_');
            $this->invalidateCache('auth_portal_dashboard_');
            AuthHelper::clearAuthUserCache($userId);

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

        // Invalidate user caches
        $this->invalidateCache('auth_users_');
        AuthHelper::clearAuthUserCache($userId);

        return $this->respond(['message' => 'User deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function getUser($userId)
    {
        $cacheKey = Utils::generateHashedCacheKey('auth_users_', ['userId' => $userId]);
        return CacheHelper::remember($cacheKey, function () use ($userId) {
            $model = new UsersModel();
            $data = $model->find($userId);
            if (!$data) {
                return $this->respond("User not found", ResponseInterface::HTTP_BAD_REQUEST);
            }

            return $this->respond(['data' => $data,], ResponseInterface::HTTP_OK);
        }, 300); // Cache for 5 minutes (sensitive data)
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
            $filters = (array) $this->request->getVar();
            $cacheKey = Utils::generateHashedCacheKey('auth_users_', $filters);

            return CacheHelper::remember($cacheKey, function () use ($filters) {
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
            }, 300); // Cache for 5 minutes (sensitive data)
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

            // Invalidate user caches
            $this->invalidateCache('auth_users_');
            AuthHelper::clearAuthUserCache($userId);

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

            // Invalidate user caches
            $this->invalidateCache('auth_users_');
            AuthHelper::clearAuthUserCache($userId);

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
            $cacheKey = 'auth_user_types';
            return CacheHelper::remember($cacheKey, function () {
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
            }, 3600); // Cache for 1 hour (static data)
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function getPortalUserTypes()
    {
        try {
            $cacheKey = 'auth_portal_user_types';
            return CacheHelper::remember($cacheKey, function () {
                $userTypesArray = Utils::getAppSettings("userTypesNames");

                return $this->respond([
                    'data' => $userTypesArray,
                ], ResponseInterface::HTTP_OK);
            }, 3600); // Cache for 1 hour (static data)
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    /**
     * Guest signup - Step 1: Create guest record and send verification email
     * @return ResponseInterface
     */
    public function guestSignup()
    {
        try {
            $guestsModel = new \App\Models\GuestsModel();
            $verificationTokenModel = new \App\Models\EmailVerificationTokenModel();

            // Validation rules
            $rules = [
                'first_name' => 'required|min_length[2]|max_length[255]',
                'last_name' => 'required|min_length[2]|max_length[255]',
                'email' => 'required|valid_email|is_unique[guests.email]|is_unique[users.email_address]',
                'phone_number' => 'required|min_length[7]|max_length[50]',
                'id_type' => 'required|max_length[50]',
                'id_number' => 'required|is_unique[guests.id_number]|max_length[100]',
                'sex' => 'required|in_list[Male,Female,Other]'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Check if email already exists in users table
            $userModel = new UsersModel();
            $existingUser = $userModel->where('email_address', $this->request->getVar('email'))->first();
            if ($existingUser) {
                return $this->respond(['message' => 'Email address is already registered'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Generate unique_id: G-[6 random chars]-[2 digit year]
            $uniqueId = $this->generateGuestUniqueId($guestsModel);
            $guestData = $guestsModel->createArrayFromAllowedFields((array) $this->request->getVar());
            // Prepare guest data
            $guestData['unique_id'] = $uniqueId;
            $guestData['email_verified'] = false;


            // Start transaction
            $guestsModel->db->transException(true)->transStart();

            // Insert guest record
            $guestId = $guestsModel->insert($guestData);
            if (!$guestId) {
                $guestsModel->db->transRollback();
                return $this->respond(['message' => 'Failed to create guest account'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Get the created guest with UUID
            $guest = $guestsModel->find($guestId);

            // Generate verification token
            $token = $verificationTokenModel->generateToken();
            $tokenHash = $verificationTokenModel->hashToken($token);

            // Get expiration time from settings (default 24 hours)
            $expirationHours = 24;
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expirationHours} hours"));

            // Store verification token
            $tokenData = [
                'guest_uuid' => $guest['uuid'],
                'email' => $guest['email'],
                'token' => $token,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString()
            ];

            $tokenId = $verificationTokenModel->insert($tokenData);
            if (!$tokenId) {
                $guestsModel->db->transRollback();
                return $this->respond(['message' => 'Failed to generate verification token'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $guestsModel->db->transComplete();

            // Send verification email
            try {
                $this->sendVerificationEmail(
                    $guest['email'],
                    $guest['first_name'] . ' ' . $guest['last_name'],
                    $token,
                    $expirationHours
                );
            } catch (\Throwable $th) {
                //never mind. the user can request for it to be resent
            }


            return $this->respond([
                'message' => 'Registration successful. Please check your email for the verification code.',
                'guest_uuid' => $guest['uuid']
            ], ResponseInterface::HTTP_CREATED);

        } catch (\Throwable $th) {
            $guestsModel->db->transRollback();
            log_message('error', 'Guest signup error: ' . $th);
            return $this->respond(['message' => 'Server error'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Guest signup - Step 2: Verify email with token
     * @return ResponseInterface
     */
    public function verifyGuestEmail()
    {
        try {
            $guestsModel = new \App\Models\GuestsModel();
            $verificationTokenModel = new \App\Models\EmailVerificationTokenModel();

            $rules = [
                'guest_uuid' => 'required',
                'token' => 'required|exact_length[6]'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestUuid = $this->request->getVar('guest_uuid');
            $token = $this->request->getVar('token');

            // Find the token
            $tokenRecord = $verificationTokenModel->findValidToken($token);

            if (!$tokenRecord) {
                return $this->respond(['message' => 'Invalid or expired verification code'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Verify the token belongs to this guest
            if ($tokenRecord['guest_uuid'] !== $guestUuid) {
                return $this->respond(['message' => 'Invalid verification code'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Verify token hash
            if (!$verificationTokenModel->verifyToken($token, $tokenRecord['token_hash'])) {
                return $this->respond(['message' => 'Invalid verification code'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Start transaction
            $guestsModel->db->transException(true)->transStart();

            // Mark token as verified
            $verificationTokenModel->markAsVerified($tokenRecord['id']);

            // Mark guest email as verified
            $guest = $guestsModel->findByUuid($guestUuid);
            if (!$guest) {
                $guestsModel->db->transRollback();
                return $this->respond(['message' => 'Guest not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            $guestsModel->markEmailAsVerified($guestUuid);

            // Commit transaction
            $guestsModel->db->transComplete();

            return $this->respond([
                'message' => 'Email verified successfully. Completing account setup...',
                'guest_uuid' => $guestUuid
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Guest signup - Step 3: Complete signup by creating user account and setting up 2FA
     * @return ResponseInterface
     */
    public function completeGuestSignup()
    {
        try {
            $guestsModel = new \App\Models\GuestsModel();

            $rules = [
                'guest_uuid' => 'required',
                'password' => 'required|min_length[8]'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestUuid = $this->request->getVar('guest_uuid');
            $password = $this->request->getVar('password');

            // Get guest record
            $guest = $guestsModel->findByUuid($guestUuid);
            if (!$guest) {
                return $this->respond(['message' => 'Guest not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Check if email is verified
            if (!$guest['email_verified']) {
                return $this->respond(['message' => 'Email not verified'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Check if user already exists
            $userModel = new UsersModel();
            $existingUser = $userModel->where('profile_table_uuid', $guestUuid)->first();
            if ($existingUser) {
                return $this->respond(['message' => 'User account already exists'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Start transaction
            $guestsModel->db->transException(true)->transStart();
            try {
                //code...

                // Create user account
                // Create user account
                $username = $guest['email'];

                $userEntity = new User([
                    'username' => $username,
                    'password' => $password,
                    'email_address' => $guest['email'],
                    'email' => $guest['email'],
                    'active' => 1,
                    'display_name' => $guest['first_name'] . ' ' . $guest['last_name'],
                    'profile_table_uuid' => $guestUuid,
                    'profile_table' => 'guests',
                    'user_type' => 'guest',
                    'two_fa_deadline' => date('Y-m-d'),
                    'phone' => $guest['phone_number'],
                    'picture' => $guest['picture']
                ]);

                $userObject = new UsersModel();
                $userObject->save($userEntity);


                // Get the created user
                $userId = $userObject->getInsertID();
                $user = $userObject->find($userId);



                // Set up 2FA
                $gAuth = new GoogleAuthenticator();
                $secret = $gAuth->createSecret();
                $qrCodeUrl = $gAuth->getQRCodeUrl('guest', $username, $secret);

                // Save 2FA setup token
                $setupToken = bin2hex(random_bytes(32));
                $userObject->update($userId, [
                    'google_auth_secret' => null, // Not enabled yet
                    'two_fa_setup_token' => $setupToken
                ]);
            } catch (\Throwable $th) {
                log_message('error', 'Complete guest signup error - failed to create user: ' . $th->getMessage());
                log_message('error', $th->getTraceAsString());
                $guestsModel->db->transRollback();
                return $this->respond(['message' => 'Server error'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }
            $guestsModel->db->transComplete();
            try {
                // Send 2FA setup email
                $portalUrl = base_url();
                $this->send2FaSetupEmail(
                    $guest['email'],
                    $guest['first_name'] . ' ' . $guest['last_name'],
                    $secret,
                    $qrCodeUrl,
                    $user->uuid,
                    $portalUrl
                );
            } catch (\Throwable $th) {
                log_message('error', 'Complete guest signup error - failed to send 2FA setup email: ' . $th->getMessage());
            }


            return $this->respond([
                'message' => 'Account created successfully. Please check your email to complete 2FA setup.',
                'user_uuid' => $user->uuid,
                'secret' => $secret,
                'qr_code_url' => $qrCodeUrl,
                'setup_token' => $setupToken
            ], ResponseInterface::HTTP_CREATED);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Request verification code by email (when UUID is not available)
     * @return ResponseInterface
     */
    public function requestVerificationByEmail()
    {
        try {
            $guestsModel = new \App\Models\GuestsModel();
            $verificationTokenModel = new \App\Models\EmailVerificationTokenModel();

            $rules = [
                'email' => 'required|valid_email'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $email = $this->request->getVar('email');

            // Find guest by email
            $guest = $guestsModel->findByEmail($email);
            if (!$guest) {
                // For security, don't reveal if email exists or not
                return $this->respond([
                    'message' => 'If this email is registered, a verification code will be sent.',
                    'guest_uuid' => null
                ], ResponseInterface::HTTP_OK);
            }

            // Allow re-verification even if already verified
            // User must go through the process again if they restart

            // Rate limiting: Check last token creation time
            $recentToken = $verificationTokenModel
                ->where('guest_uuid', $guest['uuid'])
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($recentToken) {
                $lastSentTime = strtotime($recentToken['created_at']);
                $currentTime = time();
                $timeDifference = $currentTime - $lastSentTime;

                // Prevent resending within 60 seconds
                if ($timeDifference < 60) {
                    $remainingSeconds = 60 - $timeDifference;
                    return $this->respond([
                        'message' => "Please wait {$remainingSeconds} seconds before requesting a new code",
                        'guest_uuid' => $guest['uuid']
                    ], ResponseInterface::HTTP_TOO_MANY_REQUESTS);
                }
            }

            // Generate new verification token
            $token = $verificationTokenModel->generateToken();
            $tokenHash = $verificationTokenModel->hashToken($token);

            // Get expiration time from settings (default 24 hours)
            $expirationHours = 24;
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expirationHours} hours"));

            // Store new verification token
            $tokenData = [
                'guest_uuid' => $guest['uuid'],
                'email' => $guest['email'],
                'token' => $token,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString()
            ];

            $tokenId = $verificationTokenModel->insert($tokenData);
            if (!$tokenId) {
                return $this->respond(['message' => 'Failed to generate verification token'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Send verification email
            try {
                $this->sendVerificationEmail(
                    $guest['email'],
                    $guest['first_name'] . ' ' . $guest['last_name'],
                    $token,
                    $expirationHours
                );

                return $this->respond([
                    'message' => 'Verification code has been sent to your email',
                    'guest_uuid' => $guest['uuid']
                ], ResponseInterface::HTTP_OK);

            } catch (\Throwable $th) {
                log_message('error', 'Failed to send verification email: ' . $th->getMessage());
                return $this->respond([
                    'message' => 'Failed to send email. Please try again later.',
                    'guest_uuid' => $guest['uuid']
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Send email verification email
     */
    /**
     * Resend email verification code
     * @return ResponseInterface
     */
    public function resendVerificationCode()
    {
        try {
            $guestsModel = new GuestsModel();
            $verificationTokenModel = new \App\Models\EmailVerificationTokenModel();

            $rules = [
                'guest_uuid' => 'required'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestUuid = $this->request->getVar('guest_uuid');

            // Find guest
            $guest = $guestsModel->findByUuid($guestUuid);
            if (!$guest) {
                return $this->respond(['message' => 'Guest not found'], ResponseInterface::HTTP_NOT_FOUND);
            }


            // Check if email is already verified and a user has been created. if no user has been created, allow re-verification
            // Check if user already exists
            $userModel = new UsersModel();
            $existingUser = $userModel->where('profile_table_uuid', $guestUuid)->first();
            if ($existingUser) {
                return $this->respond(['message' => 'User account already exists. Please go to the login page and login or reset your password if you have forgotten it.'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Allow re-verification even if already verified
            // User must go through the process again if they restart

            // Rate limiting: Check last token creation time
            $recentToken = $verificationTokenModel
                ->where('guest_uuid', $guestUuid)
                ->orderBy('created_at', 'DESC')
                ->first();

            if ($recentToken) {
                $lastSentTime = strtotime($recentToken['created_at']);
                $currentTime = time();
                $timeDifference = $currentTime - $lastSentTime;

                // Prevent resending within 60 seconds
                if ($timeDifference < 60) {
                    $remainingSeconds = 60 - $timeDifference;
                    return $this->respond([
                        'message' => "Please wait {$remainingSeconds} seconds before requesting a new code"
                    ], ResponseInterface::HTTP_TOO_MANY_REQUESTS);
                }
            }

            // Generate new verification token
            $token = $verificationTokenModel->generateToken();
            $tokenHash = $verificationTokenModel->hashToken($token);

            // Get expiration time from settings (default 24 hours)
            $expirationHours = 24;
            $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expirationHours} hours"));

            // Store new verification token
            $tokenData = [
                'guest_uuid' => $guest['uuid'],
                'email' => $guest['email'],
                'token' => $token,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
                'ip_address' => $this->request->getIPAddress(),
                'user_agent' => $this->request->getUserAgent()->getAgentString()
            ];

            $tokenId = $verificationTokenModel->insert($tokenData);
            if (!$tokenId) {
                return $this->respond(['message' => 'Failed to generate verification token'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            // Send verification email
            try {
                $this->sendVerificationEmail(
                    $guest['email'],
                    $guest['first_name'] . ' ' . $guest['last_name'],
                    $token,
                    $expirationHours
                );

                return $this->respond([
                    'message' => 'Verification code has been resent to your email',
                    'guest_uuid' => $guest['uuid']
                ], ResponseInterface::HTTP_OK);

            } catch (\Throwable $th) {
                log_message('error', 'Failed to send verification email: ' . $th->getMessage());
                return $this->respond([
                    'message' => 'Failed to send email. Please try again later.'
                ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get all registered guests (admin endpoint)
     */
    public function getRegisteredGuests()
    {
        try {
            $cacheKey = Utils::generateHashedCacheKey("get_guests", (array) $this->request->getVar());
            return CacheHelper::remember($cacheKey, function () {
                $guestsModel = new GuestsModel();
                $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
                $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
                $param = $this->request->getVar('param');
                $sortBy = $this->request->getVar('sortBy') ?? "id";
                $sortOrder = $this->request->getVar('sortOrder') ?? "desc";
                $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted') === "yes";

                $builder = $param ? $guestsModel->search($param) : $guestsModel->builder();
                if ($withDeleted) {
                    $guestsModel->withDeleted();
                } else {
                    $guestsModel->withDeleted(false);
                }
                // Apply filters from allowed fields
                $filterArray = $guestsModel->createArrayFromAllowedFields($this->request->getVar());
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
                    'displayColumns' => $guestsModel->getDisplayColumns(),
                    'columnFilters' => $guestsModel->getDisplayColumnFilters()
                ], ResponseInterface::HTTP_OK);
            }, 300);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Manually verify guests (bulk operation)
     */
    public function verifyGuests()
    {
        try {
            $rules = [
                'guest_uuids' => 'required|is_array',
                'guest_uuids.*' => 'required'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestUuids = $this->request->getVar('guest_uuids');
            $guestsModel = new GuestsModel();

            $updated = $guestsModel->whereIn('uuid', $guestUuids)
                ->set(['verified' => true])
                ->update();

            if (!$updated) {
                return $this->respond(['message' => 'No guests were updated'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Log activity
            $activitiesModel = new \App\Models\ActivitiesModel();
            $activitiesModel->logActivity("Verified " . count($guestUuids) . " guests", null, "guests");

            // Invalidate cache
            $this->invalidateCache('get_guests');

            return $this->respond([
                'message' => 'Guests verified successfully',
                'count' => count($guestUuids)
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Manually unverify guests (bulk operation)
     */
    public function unverifyGuests()
    {
        try {
            $rules = [
                'guest_uuids' => 'required|is_array',
                'guest_uuids.*' => 'required'
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestUuids = $this->request->getVar('guest_uuids');
            $guestsModel = new GuestsModel();

            $updated = $guestsModel->whereIn('uuid', $guestUuids)
                ->set(['verified' => false])
                ->update();

            if (!$updated) {
                return $this->respond(['message' => 'No guests were updated'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Log activity
            $activitiesModel = new \App\Models\ActivitiesModel();
            $activitiesModel->logActivity("Unverified " . count($guestUuids) . " guests", null, "guests");

            // Invalidate cache
            $this->invalidateCache('get_guests');

            return $this->respond([
                'message' => 'Guests unverified successfully',
                'count' => count($guestUuids)
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete guest user
     * Also removes the user from the users table if they exist
     */
    public function deleteGuest(?string $uuid = null)
    {
        try {
            if (!$uuid) {
                return $this->respond(['message' => 'Guest UUID is required'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $guestsModel = new GuestsModel();
            $usersModel = new UsersModel();

            // Find the guest
            $guest = $guestsModel->where('uuid', $uuid)->first();
            if (!$guest) {
                return $this->respond(['message' => 'Guest not found'], ResponseInterface::HTTP_NOT_FOUND);
            }

            // Start transaction
            $guestsModel->db->transException(true)->transStart();

            // Check if guest has a corresponding user account and delete it
            $user = $usersModel->where('email_address', $guest['email'])->first();
            if ($user) {
                $usersModel->delete($user->id);
            }

            // Delete the guest
            $deleted = $guestsModel->delete($guest['id']);
            if (!$deleted) {
                $guestsModel->db->transRollback();
                return $this->respond(['message' => 'Failed to delete guest'], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $guestsModel->db->transComplete();

            // Log activity
            $activitiesModel = new \App\Models\ActivitiesModel();
            $activitiesModel->logActivity("Deleted guest {$guest['first_name']} {$guest['last_name']} ({$guest['email']})", null, "guests");

            // Invalidate cache
            $this->invalidateCache('get_guests');

            return $this->respond([
                'message' => 'Guest deleted successfully'
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate unique ID for guest in format: G-[6 random chars]-[2 digit year]
     * Ensures uniqueness by checking against existing records
     */
    private function generateGuestUniqueId(GuestsModel $guestsModel): string
    {
        $year = date('y'); // 2-digit year
        $maxAttempts = 10;
        $attempt = 0;

        do {
            // Generate 6 random alphanumeric characters (uppercase)
            $randomChars = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            $uniqueId = "G-{$randomChars}-{$year}";

            // Check if this ID already exists
            $exists = $guestsModel->where('unique_id', $uniqueId)->first();

            if (!$exists) {
                return $uniqueId;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // If we still haven't found a unique ID after max attempts, throw an exception
        throw new \RuntimeException('Failed to generate unique guest ID after ' . $maxAttempts . ' attempts');
    }

    private function sendVerificationEmail(string $email, string $displayName, string $token, int $expirationHours): void
    {
        try {
            $templateEngine = new TemplateEngineHelper();

            $emailTemplate = Utils::getSetting(SETTING_USER_EMAIL_VERIFICATION_TEMPLATE) ?? DEFAULT_USER_EMAIL_VERIFICATION_TEMPLATE;
            $emailSubject = Utils::getSetting(SETTING_USER_EMAIL_VERIFICATION_SUBJECT) ?? DEFAULT_USER_EMAIL_VERIFICATION_SUBJECT;

            $data = [
                'name' => $displayName,
                'token' => $token,
                'expiration_hours' => $expirationHours
            ];
            log_message('info', 'Email verification data: ' . json_encode($emailTemplate));

            $content = $templateEngine->process($emailTemplate, $data);
            $subject = $templateEngine->process($emailSubject, $data);

            $emailConfig = new EmailConfig($content, $subject, $email);
            EmailHelper::sendEmail($emailConfig);
        } catch (\Throwable $th) {
            log_message('error', 'Error sending email: ' . $th);
            throw $th;
        }

    }

    /**
     * Create institution user
     * This endpoint allows admins to create users for external institutions
     * (cpd_providers, housemanship_facilities, or training_institutions)
     *
     * @return ResponseInterface
     */
    public function createInstitutionUser()
    {
        try {
            $rules = [
                "first_name" => "required|min_length[2]",
                "last_name" => "required|min_length[2]",
                "phone" => "required|min_length[10]",
                "email" => "required|valid_email|is_unique[users.email_address]|is_unique[auth_identities.secret]",
                "institution_uuid" => "required",
                "institution_type" => "required|in_list[cpd_provider,housemanship_facility,training_institution]",
            ];

            if (!$this->validate($rules)) {
                return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
            }

            $data = $this->request->getVar();
            $institutionUuid = $data['institution_uuid'];
            $institutionType = $data['institution_type'];

            // Verify institution exists
            $institutionName = $this->verifyInstitutionExists($institutionUuid, $institutionType);
            if (!$institutionName) {
                return $this->respond(['message' => 'Institution not found'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Generate unique username
            $username = $this->generateInstitutionUsername($data['first_name'], $data['last_name'], $institutionType);

            // Set user_type based on institution_type
            $userTypeMap = [
                'cpd_provider' => 'cpd',
                'housemanship_facility' => 'housemanship_facility',
                'training_institution' => 'training_institution'
            ];
            $userType = $userTypeMap[$institutionType];

            // Create user
            $userData = [
                'username' => $username,
                'email' => $data['email'],
                'email_address' => $data['email'],
                'display_name' => $data['first_name'] . ' ' . $data['last_name'],
                'phone' => $data['phone'],
                'user_type' => $userType,
                'institution_uuid' => $institutionUuid,
                'institution_type' => $institutionType,
                'active' => 1,
            ];

            $userObject = auth()->getProvider();
            $userEntityObject = new User($userData);

            if (!$userObject->save($userEntityObject)) {
                return $this->respond(['message' => $userObject->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $userId = $userObject->getInsertID();

            // Log activity
            $activitiesModel = new \App\Models\ActivitiesModel();
            $activitiesModel->logActivity("Created institution user {$username} for {$institutionName}", null, "users");

            // Send email notification
            try {
                $this->sendNewUserEmail($data['email'], $userData['display_name'], $username, $userType);
            } catch (\Throwable $th) {
                log_message('error', "Error sending email for institution user: " . $th->getMessage());
            }

            // Invalidate caches
            $this->invalidateCache('auth_users_');

            return $this->respond([
                'message' => 'Institution user created successfully',
                'data' => [
                    'id' => $userId,
                    'username' => $username,
                    'institution_name' => $institutionName
                ]
            ], ResponseInterface::HTTP_OK);

        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "An error occurred. Please try again"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Verify institution exists and return its name
     *
     * @param string $uuid Institution UUID
     * @param string $type Institution type
     * @return string|null Institution name or null if not found
     */
    private function verifyInstitutionExists(string $uuid, string $type): ?string
    {
        try {
            switch ($type) {
                case 'cpd_provider':
                    $model = new \App\Models\Cpd\CpdProviderModel();
                    $institution = $model->where('uuid', $uuid)->first();
                    return $institution['name'] ?? null;

                case 'housemanship_facility':
                    $model = new \App\Models\Housemanship\HousemanshipFacilitiesModel();
                    $institution = $model->where('uuid', $uuid)->first();
                    return $institution['name'] ?? null;

                case 'training_institution':
                    $model = new \App\Models\TrainingInstitutions\TrainingInstitutionModel();
                    $institution = $model->where('uuid', $uuid)->first();
                    return $institution['name'] ?? null;

                default:
                    return null;
            }
        } catch (\Throwable $th) {
            log_message('error', 'Error verifying institution: ' . $th->getMessage());
            return null;
        }
    }

    /**
     * Generate unique username for institution user
     * Format: [type_prefix][first_initial][last_name][random_4_digits]
     * Example: cpd_jdoe1234, hf_jsmith5678, ti_awhite9012
     *
     * @param string $firstName User's first name
     * @param string $lastName User's last name
     * @param string $institutionType Institution type
     * @return string Generated username
     */
    private function generateInstitutionUsername(string $firstName, string $lastName, string $institutionType): string
    {
        $prefixMap = [
            'cpd_provider' => 'cpd_',
            'housemanship_facility' => 'hf_',
            'training_institution' => 'ti_'
        ];

        $prefix = $prefixMap[$institutionType] ?? 'inst_';

        // Get first initial and clean last name
        $firstInitial = strtolower(substr($firstName, 0, 1));
        $cleanLastName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));

        $userModel = new UsersModel();
        $maxAttempts = 10;
        $attempt = 0;

        do {
            // Generate 4 random digits
            $randomDigits = str_pad((string) rand(0, 9999), 4, '0', STR_PAD_LEFT);
            $username = $prefix . $firstInitial . $cleanLastName . $randomDigits;

            // Check if username exists
            $exists = $userModel->where('username', $username)->first();

            if (!$exists) {
                return $username;
            }

            $attempt++;
        } while ($attempt < $maxAttempts);

        // Fallback: use timestamp if all attempts fail
        return $prefix . $firstInitial . $cleanLastName . time();
    }
}
