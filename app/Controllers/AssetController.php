<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class AssetController extends ResourceController
{
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
     * upload a new file. the type specifies which subfolder it should be placed in
     *
     * @return mixed
     */
    public function upload(string $type)
    {
        try {
            //code...

            $validationRule = [
                'uploadFile' => [
                    'label' => 'Image File',
                    'rules' => [
                        'uploaded[uploadFile]',
                        'is_image[uploadFile]',
                        'mime_in[uploadFile,image/jpg,image/jpeg,image/gif,image/png,image/webp]',
                        'max_size[uploadFile,1000]',
                        // 'max_dims[uploadFile,1024,768]',
                    ],
                ],
            ];
            if (!$this->validate($validationRule)) {
                return $this->respond(
                $this->validator->getErrors(), ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
            }

            $img = $this->request->getFile('uploadFile');
            $destination = $this->getImageDirectory($type);

            if (!$img->hasMoved()) {
                $filepath = $destination . $img->store($destination);

                $data = ['filePath' => $filepath];
                return $this->respond($data, ResponseInterface::HTTP_OK);
            }
        } catch (\Throwable $th) {
            //throw $th;
            return $this->respond(['message' => "Server error"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

    private function getImageDirectory(string $type):string{
        $baseFolder = '';
            $destination = $baseFolder;
            switch ($type) {
                case 'practitioners_images':
                    $destination = "practitioners_images/";
                    break;
                case "documents":
                    $destination = "documents/";

                default:

                    break;
            }
            return $destination;
    }

    public function serveFile($imageName, $type)
    {
        $directory = $this->getImageDirectory($type);
        $filePath = WRITEPATH . 'uploads/'.$directory.$imageName;
        if(!file_exists($filePath)) {
            return $this->respond(['message' => "Image not found"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
        try {        
        $image = file_get_contents($filePath);
        // choose the right mime type
        $mimeType = $this->getMimeType($imageName);

        $this->response
            ->setStatusCode(200)
            ->setContentType($mimeType)
            ->setBody($image)
            ->send();
        } catch (\Throwable $th) {
            return $this->respond(['message' => "Invalid file"], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function getMimeType($filename) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimeType;
    }

}
