<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;
use App\Helpers\Constants;

/**
 * @OA\Info(title="API Name", version="1.0")
 * @OA\Tag(name="Tag Name", description="Tag description")
 * @OA\Tag(
 *     name="Asset",
 *     description="Operations for managing and viewing system assets"
 * )
 */
class AssetController extends ResourceController
{


    /**
     * upload a new file. the type specifies which subfolder it should be placed in
     *
     * @return mixed
     */
    public function upload(string $type)
    {
        $allowedTypes = [
            'practitioners_images',
            'documents',
            'applications'
        ];
        if (!in_array($type, $allowedTypes)) {
            return $this->respond(['message' => "Invalid file type"], ResponseInterface::HTTP_BAD_REQUEST);
        }
        $mimes = "image/jpg,image/jpeg,image/gif,image/png,image/webp,image/svg+xml,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
        if ($type === "practitioners_images") {
            $mimes = "image/jpg,image/jpeg,image/png";
        }
        $validationRule = [
            'uploadFile' => [
                'label' => 'Uploaded File',
                'rules' => [
                    // 'uploaded[uploadFile]',
                    "mime_in[uploadFile,$mimes]",
                    "max_size[uploadFile,5000]",
                    // 'max_dims[uploadFile,1024,768]',
                ],
            ],
        ];
        if (!$this->validate($validationRule)) {
            $message = implode(" ", array_values($this->validator->getErrors()));
            return $this->respond(['message' => $message], ResponseInterface::HTTP_BAD_REQUEST);

        }

        $img = $this->request->getFile('uploadFile');
        $destination = $this->getImageDirectory($type);

        if (!$img->hasMoved()) {
            $filepath = $destination . $img->store($destination);
            $filepathParts = explode("/", $filepath);
            $fileName = array_pop($filepathParts);
            $data = [
                'filePath' => $fileName,
                'fullPath' => base_url("file-server/image-render/$type/$fileName")
            ];
            return $this->respond($data, ResponseInterface::HTTP_OK);
        }

    }

    private function getImageDirectory(string $type): string
    {
        $baseFolder = '';
        $destination = $baseFolder;
        switch ($type) {
            case 'practitioners_images':
                $destination = PRACTITIONERS_ASSETS_FOLDER . "/";
                break;
            case "documents":
                $destination = "documents/";
                break;
            case "applications":
                $destination = APPLICATIONS_ASSETS_FOLDER . "/";
                break;
            default:

                break;
        }
        return $destination;
    }

    public function serveFile($type, $imageName)
    {
        $directory = $this->getImageDirectory($type);
        $filePath = WRITEPATH . 'uploads/' . $directory . $imageName;
        if (!file_exists($filePath)) {
            return $this->respond(['message' => "Image not found"], ResponseInterface::HTTP_BAD_REQUEST);
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
            log_message("error", $th);
            return $this->respond(['message' => "Invalid file"], ResponseInterface::HTTP_BAD_REQUEST);
        }

    }

    public function getMimeType($filename)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filename);
        finfo_close($finfo);
        return $mimeType;
    }

}
