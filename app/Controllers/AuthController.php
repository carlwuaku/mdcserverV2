<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Entities\User;

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
        $userObject = new UserModel();
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
            $userObject = new UserModel();
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
        $userObject = new UserModel();
        $userData = $userObject->findById($userId);
        return $this->respondCreated([
            "status" => true,
            "message" => "Profile",
            "data" => [
                "user" => $userData
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
