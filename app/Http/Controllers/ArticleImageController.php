<?php

namespace App\Http\Controllers;

use App\Lib\MTResponse;
use App\Models\ArticleImage;
use Illuminate\Http\Request;

class ArticleImageController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function delete(Request $request, $env_id){

        $deployEnvObj = new  ArticleImage();
        $ret_insert = $deployEnvObj->deleteOne($env_id);

        if ($ret_insert) {
            MTResponse::jsonResponse("ok", RESPONSE_SUCCESS);
        } else {
            MTResponse::jsonResponse("error", RESPONSE_ERROR);
        }
    }

}
