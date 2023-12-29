<?php

namespace App\Controllers;

use App\Models\PermissionsModel;
use App\Models\RolePermissionsModel;
use App\Models\RolesModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Entities\User;
use App\Models\UsersModel;

class AuthController extends ResourceController
{

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
            $response = [
                "status" => false,
                "message" => "Invalid login details",
                "data" => []
            ];
        } else {
            $userObject = new UsersModel();
            $userData = $userObject->findById(auth()->id());
            $token = $userData->generateAccessToken("somesecretkey");
            $response = [
                "status" => true,
                "message" => "User logged in successfully",
                "data" => [
                    "token" => $token->raw_token
                ]
            ];
        }

        return $this->respondCreated($response);
    }

    public function profile()
    {
        $userId = auth()->id();
        $userObject = new UsersModel();
        $userData = $userObject->findById($userId);
        if(!$userData){
            return $this->respond("User not found", ResponseInterface::HTTP_NOT_FOUND);
        }
        $permissionsObject = new PermissionsModel();
        $permissions = $permissionsObject->getRolePermissions($userData->role_id, true);
        return $this->respondCreated([
            "status" => true,
            "message" => "Profile",
            "data" => [
                "user" => $userData,
                "permissions"=> $permissions
            ]
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
                ->setJSON(['error' => $result->reason()])
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
        return $this->respond(['role' => $data, 'permissions' => $permissions, 'excludedPermissions' => $excludedPermissions], ResponseInterface::HTTP_OK);
    }

    public function getRoles()
    {
        $model = new RolesModel();
        return $this->respond(['data' => $model->paginate(3), 'pager' => $model->pager->getDetails()], ResponseInterface::HTTP_OK);
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
        return $this->respond(['message' => 'Permission added to role'], ResponseInterface::HTTP_OK);
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
    public function deleteRolePermission($id)
    {
        $model = new RolePermissionsModel();
        if (!$model->delete($id)) {
            return $this->respond(['message' => $model->errors()], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        return $this->respond(['message' => 'Permission deleted from role successfully'], ResponseInterface::HTTP_OK);
    }


    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    public function index()
    {
        //
    }

    /**
     * Return the properties of a resource object
     *
     * @return mixed
     */
    public function show($id = null)
    {
        //
    }

    /**
     * Return a new resource object, with default properties
     *
     * @return mixed
     */
    public function new()
    {
        //
    }

    /**
     * Create a new resource object, from "posted" parameters
     *
     * @return mixed
     */
    public function create()
    {
        //
    }

    /**
     * Return the editable properties of a resource object
     *
     * @return mixed
     */
    public function edit($id = null)
    {
        //
    }

    /**
     * Add or update a model resource, from "posted" properties
     *
     * @return mixed
     */
    public function update($id = null)
    {
        //
    }

    /**
     * Delete the designated resource object from the model
     *
     * @return mixed
     */
    public function delete($id = null)
    {
        //
    }
}
