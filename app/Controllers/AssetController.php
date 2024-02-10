<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class AssetController extends ResourceController
{
    

    /**
     * upload a new file. the type specifies which subfolder it should be placed in
     *
     * @return mixed
     */
    public function upload(string $type)
    {
       
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
                $filepathParts = explode("/", $filepath);
                $fileName = array_pop($filepathParts);
                $data = ['filePath' => $fileName, 
                'fullPath' => base_url("file-server/image-render/$fileName/$type")];
                return $this->respond($data, ResponseInterface::HTTP_OK);
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
        $mimeType = $this->getMimeType($filePath);

        $this->response
            ->setStatusCode(200)
            ->setContentType($mimeType)
            ->setBody($image)
            ->send();
        } catch (\Throwable $th) {
            log_message("error", $th->getMessage());
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
