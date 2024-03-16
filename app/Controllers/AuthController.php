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
class AuthController extends ResourceController
{

    public function appName()
    {
        return $this->respond(['data' => 'MDC Management System'], ResponseInterface::HTTP_OK);
    }

    public function register()
    {
        $rules = [
            "username" => "required|is_unique[users.username]",
            "password" => "required",
            "email" => "required|valid_email|is_unique[auth_identities.secret]",
        ];
        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);

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
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
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
        $permissionsObject = new PermissionsModel();
        $permissions = $permissionsObject->getRolePermissions($userData->role_id, true);
        $userData->permissions = $permissions;
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
            return $this->respond("User not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $permissionsObject = new PermissionsModel();
        $permissions = $permissionsObject->getRolePermissions($userData->role_id, true);
        $userData->permissions = $permissions;
        return $this->respondCreated([
            "user" => $userData,
            "permissions" => $permissions
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

    //add permissions to role_id, remove permission from role_id, create a role, edit a role,

    public function createRole()
    {
        $rules = [
            "role_name" => "required|is_unique[roles.role_name]"
        ];

        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getPost();
        $model = new RolesModel();
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
        }
        $data = $this->request->getVar();
        //restore it if it had been deleted
        $data->deleted_at = null;
        $data->role_id = $role_id;
        $model = new RolesModel();
        if (!$model->update($role_id, $data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Role updated successfully'], ResponseInterface::HTTP_OK);
    }

    public function deleteRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->delete($role_id)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Role deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function restoreRole($role_id)
    {
        $model = new RolesModel();
        if (!$model->update($role_id, ['deleted_at'=> null])) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Role restored successfully'], ResponseInterface::HTTP_OK);
    }

    public function getRole($role_id)
    {
        $model = new RolesModel();
        $data = $model->find($role_id);
        if (!$data) {
            return $this->respond("Role not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        $permissionsModel = new PermissionsModel();
        $permissions = $permissionsModel->getRolePermissions($role_id);
        $excludedPermissions = $permissionsModel->getRoleExcludedPermissions($role_id);
        return $this->respond(['data' => $data, 'permissions' => $permissions, 'excludedPermissions' => $excludedPermissions], ResponseInterface::HTTP_OK);
    }

    public function getRoles()
    {
        $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
        $page = $this->request->getVar('page') ? (int) $this->request->getVar('page') : 0;
        $withDeleted = $this->request->getVar('withDeleted') && $this->request->getVar('withDeleted')  === "yes";
        $param = $this->request->getVar('param');
        $model = new RolesModel();
        $builder = $param ? $model->search($param) : $model->builder();
        $builder->join('users', "roles.role_id = users.role_id","left")
        ->select("roles.*, count(users.id) as number_of_users")
        ->groupBy('roles.role_id');
        if($withDeleted){
            $model->withDeleted();
        }
        $totalBuilder = clone $builder;
        $total = $totalBuilder->countAllResults();
        $result = $builder->get($per_page, $page)->getResult();
        
        return $this->respond(['data' => $result, 'total' => $total,
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
        $data = $this->request->getPost();
        $model = new RolePermissionsModel();
        if (!$model->insert($data)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
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
    public function deleteRolePermission($roleId, $permissionId)
    {
        $model = new RolePermissionsModel();
        if (!$model->where("role_id = $roleId and permission_id = $permissionId")->delete(null, true)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Permission deleted from role successfully'], ResponseInterface::HTTP_OK);
    }



    public function createUser()
    {
        $rules = [
            "username" => "required|is_unique[users.username]",
            "email" => "required|valid_email|is_unique[auth_identities.secret]",
            "phone" => "required|min_length[10]",
            "role_id" => "required|is_natural_no_zero|is_not_unique[roles.role_id]",
            "password" => "required|min_length[8]|strong_password[]",
            "password_confirm" => "required|matches[password]",
        ];
        if (!$this->validate($rules)) {
            return $this->respond($this->validator->getErrors(), ResponseInterface::HTTP_BAD_REQUEST);
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
    //         return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    //     return $this->respond(['message' => 'User updated successfully'], ResponseInterface::HTTP_OK);
    // }


    public function updateUser($userId)
{
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
       "role_id" => "permit_empty|is_natural_no_zero|is_not_unique[roles.role_id]",
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

   // If a new password was provided, hash it before saving
//    if (isset($data['password']) && $data['password'] !== '') {
//        $existingUser->password = password_hash($data['password'], PASSWORD_DEFAULT);
//    }

   $userObject->save($existingUser);

   return $this->respond(['message' => 'User updated successfully'], ResponseInterface::HTTP_OK);
}

    public function deleteUser($userId)
    {
        $model = new UsersModel();
        if (!$model->delete($userId)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'User deleted successfully'], ResponseInterface::HTTP_OK);
    }

    public function getUser($userId)
    {
        $model = new UsersModel();
        $data = $model->find($userId);
        if (!$data) {
            return $this->respond("User not found", ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->respond(['data' => $data,], ResponseInterface::HTTP_OK);
    }

    public function getUsers()
    {
        $per_page = $this->request->getVar('limit') ? (int) $this->request->getVar('limit') : 100;
        $model = new UsersModel();
        $builder = $model->builder();
        $builder->join('roles', "roles.role_id = {$model->tableName}.role_id")
        ->select("$model->tableName.*, roles.role_name");

        return $this->respond(['data' => $model->withDeleted()->paginate($per_page), 'pager' => $model->pager->getDetails(),
            'displayColumns' => $model->getDisplayColumns()
        ], ResponseInterface::HTTP_OK);
    }

    public function banUser($userId){
        $userObject = new UsersModel();
        $reason = $this->request->getVar('reason') ?? '' ;
        /** @var UsersModel $user */
        $user = $userObject->find($userId);
        if (!$user) {
            return $this->respond(['message' => 'User not found'], ResponseInterface::HTTP_BAD_REQUEST);
        }
        
        $user->ban($reason);
        return $this->respond(['message' => 'User banned successfully'], ResponseInterface::HTTP_OK);
    }

    public function unbanUser($userId){
        $userObject = new UsersModel();
        /** @var UsersModel $user */
        $user = $userObject->find($userId);
        if (!$user) {
            return $this->respond(['message' => 'User not found'], ResponseInterface::HTTP_BAD_REQUEST);
        }
        
        $user->unBan();
        return $this->respond(['message' => 'User unbanned successfully'], ResponseInterface::HTTP_OK);
    }

    public function migrate(){
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
            echo print_r($output, true);
            // Migration failed
            echo "not successful";
        }
    }
}
