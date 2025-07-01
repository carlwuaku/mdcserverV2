<?php

namespace App\Controllers;

use App\Helpers\Utils;
use App\Models\PermissionsModel;
use App\Models\RolePermissionsModel;
use App\Models\RolesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use App\Models\UsersModel;
use CodeIgniter\Database\MigrationRunner;
use Google\ReCaptcha\ReCaptcha;
use ReCaptcha\ReCaptcha as ReCaptchaReCaptcha;
use App\Helpers\CacheHelper;
use Vectorface\GoogleAuthenticator;
use CodeIgniter\I18n\Time;

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

    public function appSettings()
    {
        // return CacheHelper::remember('app_settings', function() {
        //read the data from app-settings.json at the root of the project
        try {
            $fileName = Utils::getAppSettingsFileName();
            $data = json_decode(file_get_contents($fileName), true);

            //if logo is set, append the base url to it
            if (isset($data['logo'])) {
                $data['logo'] = base_url() . $data['logo'];
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
            return $this->respond($data, ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => 'App settings file not found'], ResponseInterface::HTTP_NOT_FOUND);
        }

        // }, 3600); // Cache for 1 hour
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
            "email" => "required|valid_email|is_unique[auth_identities.secret]",
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
        $userObject = new UsersModel();

        $userData = $userObject->where(["uuid" => $uuid])->first();

        // Create Google Authenticator object
        $authenticator = new GoogleAuthenticator();

        // Generate a secret key
        $secret = $authenticator->createSecret();

        // Create the QR code URL
        $appName = getenv("GOOGLE_AUTHENTICATOR_APP_NAME"); // Replace with your app name
        $email = $userData->email;
        $qrCodeUrl = $authenticator->getQRCodeUrl($email, $secret, $appName);

        // Save the secret key to the user's record in the database
        $userObject->update($userData->id, [
            'two_fa_setup_token' => $secret
        ]);

        return $this->respond([
            'secret' => $secret, // User can manually enter this if they can't scan QR
            'qr_code_url' => $qrCodeUrl,
            'message' => 'Scan this QR code with Google Authenticator app'
        ], ResponseInterface::HTTP_OK);
    }

    public function verifyAndEnableGoogleAuth()
    {
        $rules = [
            'code' => 'required|min_length[6]|max_length[6]|numeric',
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
        $code = $this->request->getVar('code');

        if (!$authenticator->verifyCode($secret, $code, 2)) {
            return $this->respond([
                'message' => 'Invalid verification code'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
        // Code is valid, save the secret to the user's record

        // $userData->google_auth_secret = $secret;
        // $userData->two_fa_setup_token = null; // Clear the setup token
        // $userObject->save($userData);
        $userObject->update($userData->id, [
            'two_fa_setup_token' => null,
            'google_auth_secret' => $secret,
        ]);

        return $this->respond([
            'message' => '2FA has been successfully enabled for your account'
        ], ResponseInterface::HTTP_OK);
    }

    public function disableGoogleAuth()
    {


        $userId = $this->request->getVar('user_id');
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);


        // Remove 2FA
        $userData->google_auth_secret = null;
        $userObject->save($userData);

        return $this->respond([
            'message' => '2FA has been successfully disabled for this account'
        ], ResponseInterface::HTTP_OK);
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
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);
        if (!$userData) {
            return $this->respond(["message" => "User not found"], ResponseInterface::HTTP_NOT_FOUND);
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

                $userData->profile_data = $profileData;
            }
        }
        $userData->permissions = $permissionsList;
        return $this->respondCreated([
            "user" => $userData,
            "permissions" => $permissionsList
        ]);
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
        // Check if 2FA code is required for this request
        $is2faVerification = $this->request->getVar('verification_mode') === '2fa';
        // Validate credentials
        $rules = setting('Validation.login') ?? [
            'email' => config('auth')->emailValidationRules,
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
                    'rules' => "required|string|in_list['admin portal','practitioners portal']",
                ],
            ];
        }


        if (!$this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(401);
        }
        //make sure the user_type is a valid one
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
            // Get the credentials for login
            $credentials = $this->request->getPost(setting('Auth.validFields'));
            $credentials = array_filter($credentials);
            $credentials['password'] = $this->request->getPost('password');

            // Attempt to login
            $result = auth()->attempt($credentials);
            if (!$result->isOK()) {
                return $this->response
                    ->setJSON(['message' => $result->reason()])
                    ->setStatusCode(401);
            }
            //check if the user is the correct type
            $userObject = new UsersModel();
            $userData = $userObject->findById(auth()->id());
            if ($userData->user_type !== $userType) {
                return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
            }
            // if the device name is admin portal, check if the user is an admin
            if ($deviceName === 'admin portal' && $userData->user_type !== 'admin') {
                return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // if the device name is practitioners portal, make sure the user is not an admin
            if ($deviceName === 'practitioners portal' && $userData->user_type === 'admin') {
                return $this->respond(['message' => 'Invalid user type'], ResponseInterface::HTTP_BAD_REQUEST);
            }


            // check if the user's two_fa_deadline is set and if it is in the past and has not set up 2FA
            if ($userData->two_fa_deadline && $userData->two_fa_deadline < date('Y-m-d') && empty($userData->google_auth_secret)) {
                return $this->respond(['message' => 'The deadline to enable 2 factor authentication has passed. Please contact our office for support.'], ResponseInterface::HTTP_BAD_REQUEST);
            }
            // Check if 2FA is enabled for this user
            if (!empty($userData->google_auth_secret)) {
                // Don't actually log them in yet - require 2FA verification
                auth()->logout();
                //generate a new random token
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
        return $this->respond(['message' => 'Role updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deleteRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->delete($role_id)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
        return $this->respond(['message' => 'Role deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restoreRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->update($role_id, (object) ['deleted_at' => null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
        }
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
            "email" => "required|valid_email|is_unique[auth_identities.secret]",
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
        $userEntityObject = new User(
            $data
        );
        $userObject->save($userEntityObject);

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
                "email" => "required|valid_email|is_unique[auth_identities.secret]",
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

                try {
                    $userEntityObject = new User($userData);
                    $userObject->save($userEntityObject);
                    $id = $userObject->getInsertID();

                    $results[] = [
                        'status' => 'success',
                        'message' => 'User created successfully',
                        'data' => $id
                    ];
                } catch (\Exception $e) {
                    $results[] = [
                        'status' => 'error',
                        'message' => $e,
                        'data' => $userData['username'] ?? 'Unknown user'
                    ];
                }
            }

            // If all users failed, return a bad request status
            if (
                count(array_filter($results, function ($item) {
                    return $item['status'] === 'error';
                })) === count($results)
            ) {
                return $this->respond(['message' => 'All user creations failed', 'details' => $results], ResponseInterface::HTTP_BAD_REQUEST);
            }

            // Otherwise return success with details
            return $this->respond(['message' => 'Users processed', 'details' => $results], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th);
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
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
                "email" => "permit_empty|valid_email|is_unique[auth_identities.secret,user_id,$userId]",
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

            $builder->select("id, uuid, display_name, user_type, username, status, status_message, active, created_at, regionId, position, picture, phone, email, role_name, CASE WHEN google_auth_secret IS NOT NULL THEN 'yes' ELSE 'no' END AS google_authenticator_setup")
            ;

            $builder->orderBy($sortBy, $sortOrder);
            $totalBuilder = clone $builder;
            $total = $totalBuilder->countAllResults();
            $result = $builder->get($per_page, $page)->getResult();

            return $this->respond([
                'data' => $result,
                'total' => $total,
                'displayColumns' => $model->getDisplayColumns()
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
}
