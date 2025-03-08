<?php

namespace App\Controllers;

use App\Models\PermissionsModel;
use App\Models\RolePermissionsModel;
use App\Models\RolesModel;
use CodeIgniter\Config\Config;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use App\Models\UsersModel;
use CodeIgniter\Database\MigrationRunner;
use Google\ReCaptcha\ReCaptcha;
use ReCaptcha\ReCaptcha as ReCaptchaReCaptcha;
use App\Helpers\CacheHelper;

class AuthController extends ResourceController
{

    public function appSettings()
    {
        return CacheHelper::remember('app_settings', function() {
            //read the data from app-settings.json at the root of the project
            $data = json_decode(file_get_contents(ROOTPATH . 'app-settings.json'), true);
            //if logo is set, append the base url to it
            if (isset($data['logo'])) {
                $data['logo'] = base_url() . $data['logo'];
            }
            $data['recaptchaSiteKey'] = getenv('RECAPTCHA_PUBLIC_KEY');
            return $this->respond($data, ResponseInterface::HTTP_OK);
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

    public function register()
    {
        $rules = [
            "username" => "required|is_unique[users.username]",
            "password" => "required",
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

    public function login()
    {
        if (auth()->loggedIn()) {
            auth()->logout();
        }
        $rules = [
            "email" => "required|valid_email",
            "password" => "required"
        ];
        if (!$this->validate($rules)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);
        }
        // success
        $credentials = [
            "email" => $this->request->getVar("email"),
            "password" => $this->request->getVar("password")
        ];

        $loginAttempt = auth()->attempt($credentials);
        if (!$loginAttempt->isOK()) {
            return $this->respond(["message" => "Wrong combination. Try again"], ResponseInterface::HTTP_NOT_FOUND);

        }

        $userObject = new UsersModel();
        $userData = $userObject->findById(auth()->id());
        $token = $userData->generateAccessToken("somesecretkey");
        // $permissionsObject = new PermissionsModel();
        // $permissions = $permissionsObject->getRolePermissions($userData->role_id, true);
        // $userData->permissions = $permissions;
        $response = [
            "token" => $token->raw_token,
            "user" => $userData,

        ];


        return $this->respondCreated($response);
    }

    /**
     * @api {get} /profile get the details of the currently logged in user making the request
     * @apiName profile
     * @apiGroup Authentication
     *
     * @apiSuccess {['user' => UserObject, 'permissions' => [string array]]} Permission added to Role successfully.
     */
    public function profile()
    {
        $userId = auth()->id();
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);
        if (!$userData) {
            return $this->respond(["message" => "User not found"], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $rpObject = new RolePermissionsModel();
        $permissions = $rpObject->where("role", $userData->role_name)->findAll();
        $permissionsList = [];
        foreach ($permissions as $permission) {
            $permissionsList[] = $permission['permission'];
        }
        $userData->permissions = $permissionsList;
        return $this->respondCreated([
            "user" => $userData,
            "permissions" => $permissionsList
        ]);
    }

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
        // Validate credentials
        $rules = setting('Validation.login') ?? [
            'email' => config('Auth')->emailValidationRules,
            'password' => [
                'label' => 'Auth.password',
                'rules' => 'required',
            ],
            'device_name' => [
                'label' => 'Device Name',
                'rules' => 'required|string',
            ],
        ];

        if (!$this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return $this->response
                ->setJSON(['errors' => $this->validator->getErrors()])
                ->setStatusCode(401);
        }

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

    //add permissions to role_name, remove permission from role_name, create a role, edit a role,

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



    public function createUser()
    {
        $rules = [
            "username" => "required|is_unique[users.username]",
            "email" => "required|valid_email|is_unique[auth_identities.secret]",
            "phone" => "required|min_length[10]",
            "role_name" => "required|is_not_unique[roles.role_name]",
            "password" => "required|min_length[8]|strong_password[]",
            "password_confirm" => "required|matches[password]",
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

    // public function updateUser($userId)
    // {
    //     $rules = [
    //         "username" => "required|is_unique[users.username,id,{$userId}]",
    //         "email" => "required|valid_email|is_unique[auth_identities.secret,id,{$userId}]",
    //         "phone" => "required|min_length[10]",
    //         "role_id" => "required|is_natural_no_zero|is_not_unique[roles.role_id]",
    //         "password" => "if_exist|min_length[8]",
    //         "password_confirm" => "required_with[password]|matches[password]",
    //     ];
    //     if (!$this->validate($rules)) {
    //         return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     $data = $this->request->getVar();

    //     //restore it if it had been deleted
    //     $data->id = $userId;
    //     // log_message('info',$data);
    //     $model = new UsersModel();
    //     if (!$model->update($userId, $data)) {
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_BAD_REQUEST);
    //     }
    //     return $this->respond(['message' => 'User updated successfully'], ResponseInterface::HTTP_OK);
    // }


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

            // If a new password was provided, hash it before saving
//    if (isset($data['password']) && $data['password'] !== '') {
//        $existingUser->password = password_hash($data['password'], PASSWORD_DEFAULT);
//    }

            $userObject->save($existingUser);

            return $this->respond(['message' => 'User updated successfully'], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
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

    public function getUsers()
    {
        try {
            $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
            $model = new UsersModel();
            $builder = $model->builder();

            return $this->respond([
                'data' => $model->withDeleted()->paginate($per_page),
                'pager' => $model->pager->getDetails(),
                'displayColumns' => $model->getDisplayColumns()
            ], ResponseInterface::HTTP_OK);
        } catch (\Throwable $th) {
            log_message('error', $th->getMessage());
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
            log_message('error', $th->getMessage());
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
            log_message('error', $th->getMessage());
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function migrate()
    {
        $config = Config::get('Migrations');
        $migration = new MigrationRunner(
            $config
        );


        try {
            echo print_r($migration->findMigrations(), true);
            $migration->latest();
            echo "Migrations run successfully.";
        } catch (\Exception $e) {
            echo "Error running migrations: " . $e->getMessage();
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
}
