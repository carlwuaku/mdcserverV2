<?php 
namespace App\Controllers;
use App\Models\NewsModel;

class News extends BaseController{

    public function index()
    {
        $model = model(NewsModel::class);

        $data['news'] = $model->getNews();
        return view('templates/header', $data).view('news/index').view('templates/footer');

    }
    public function show($slug = null ){
        /** @var NewsModel $model  */
        $model = model(NewsModel::class);
        $data['news'] = $model->getNews();
    }
}
