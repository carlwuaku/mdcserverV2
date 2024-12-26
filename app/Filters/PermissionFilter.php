<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use App\Models\RolePermissionsModel;

class PermissionFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        helper("auth");
        $response = service('response');
        if (!auth("tokens")->loggedIn()) {
            $response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setBody('you are not logged in')->send();
            exit();
        }
        if ($arguments) {
            //the arguments would be permissions the user needs to have
            $rpModel = new RolePermissionsModel();
            foreach ($arguments as $permission) {
                if (!$rpModel->hasPermission(auth()->getUser()->role_id, $permission)) {
                    $response->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)->setBody('you are not permitted to perform this action')->send();
                    exit();
                }
            }

        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}
