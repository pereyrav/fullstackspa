<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Post;
use App\Helpers\JwtAuth;

class PostController extends Controller
{
    public function __construct(){
        $this->middleware('api.auth', ['except' => [
            'index', 
            'show', 
            'getImage', 
            'getPostByCategory', 
            'getPostByUser'
            ]]);
    }
    
    public function index(){
        $posts = Post::all()->Load('category')
                            ->load('user');
        
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts,
        ], 200);
    }
    
    public function show($id){
        $post = Post::find($id)->load('category');
        
        if(is_object($post)){
           $data = [
            'code' => 200,
            'status' => 'success',
            'posts' => $post,
        ];
        }else{
            $data = [
            'code' => 404,
            'status' => 'error',
            'posts' => 'la entrada no existe',
        ];
        }
        return response()->json($data, $data['code']);
    }
    
    public function store(Request $request){
        //Recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json);
        $params_array = json_decode($json, true);
        
        if(!empty($params_array)){
        //Conseguir user identificado
        $user = $this->getIdentity($request);
        
        //validar datos
        $validate = \Validator::make($params_array, [
           'title' => 'required',
            'content' => 'required',
            'category_id' => 'required',
            'image' => 'required',
        ]);
        
        if($validate->fails()){
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'No se ha guardado el post, faltan datos',
            ];
        }else{
        //guardar el articulo
            $post = new Post();
            $post->user_id = $user->sub;
            $post->category_id = $params->category_id;
            $post->title = $params->title;
            $post->content = $params->content;
            $post->image = $params->image;
            $post->save();
            
            $data = [
                'code' => 200,
                'status' => 'success',
                'post' => $post,
            ];
        }
        
        }else{
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Envía los datos correctamente',
            ];
        }
        //devolver la respuesta
        return response()->json($data, $data['code']);
    }
    
    public function update($id, Request $request){
        //Recoger datos por post
        $json = $request->input('json', null);
        $params_array = json_decode($json, true);
        
        //Datos para devolver algo
        $data = array(
            'code' => 400,
            'status' => 'error',
            'message' => 'datos enviados incorrectamente',
        );
        
        if(!empty($params_array)){
       //validar datos
    $validate = \Validator::make($params_array, [
        'title' => 'required',
        'content' => 'required',
        'category_id' => 'required',
    ]);
    
        if($validate->fails()){
            $data['errors'] = $validate->errors();
            return response()->json($data, $data['code']);
        }
        //eliminar del array lo que no queremos actualizar
        unset($params_array['id']);
        unset($params_array['user_id']);
        unset($params_array['created_at']);
        unset($params_array['user']);
        
        //Conseguir user identificado
        $user = $this->getIdentity($request);
        
        //Buscar el registrp
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first();
        
        if(!empty($post) && is_object ($post)){
           //actualizar el registro
            $post->update($params_array);
            //devolver algo
            $data = array(
            'code' => 200,
            'status' => 'success',
            'post' => $post,
            'changes' => $params_array,
        );
        }
        /*
        $where = [
            'id' => $id,
            'user_id' => $user->sub,
        ];
        $post = Post::updateOrCreate($where, $params_array);
        */
        //devolver resultado
        $data = array(
            'code' => 200,
            'status' => 'success',
            'post' => $post,
            'changes' => $params_array,
        );
        
        }
        return response()->json($data, $data['code']);
    }
    
    public function destroy($id, Request $request){
        //Conseguir user identificado
        $user = $this->getIdentity($request);
        
        //Conseguir el registro
        $post = Post::where('id', $id)
                ->where('user_id', $user->sub)
                ->first();
        
        if(!empty($post)){
        //Borrarlo
        $post->delete();
        
        //devolver resultado
        $data = [
            'code' => 200,
            'status' => 'success',
            'post' => $post,
        ];
        }else{
          $data = [
            'code' => 2404,
            'status' => 'error',
            'message' => 'el post no existe',
        ];  
        }
        return response()->json($data, $data['code']);
    }
    
    private function getIdentity($request){
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);
        
        return $user;
    }
    
    public function upload(Request $request){
        //Recoger imagen de la peticion, el archivo
        $image = $request->file('file0');
        
        //validar la imagen
        $validate = \Validator::make($request->all(), [
            'file0' => 'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        //guardar imagen en disco Images
        if(!$image || $validate->fails()){
            $data = [
                'code' => 400,
                'status' => 'error',
                'message' => 'Error al subir la imagen',
            ];
        }else{
            $image_name = time().$image->getClientOriginalName();
            
            \Storage::disk('images')->put($image_name, \File::get($image));
            
            $data = [
                'code' => 200,
                'stauts' => 'success',
                'image' => $image_name
            ];
        }
        //devolver resultado
        return response()->json($data, $data ['code']);
    }
    
    public function getImage($filename){
        //Comprobar si existe el archivo
        $isset = \Storage::disk('images')->exists($filename);
        
        if($isset){
        //Conseguir la imagen
        $file = \Storage::disk('images')->get($filename);
        //devolver la imagen
        return new Response($file, 200);
        }else{
            $data = [
                'code' => 404,
                'status' => 'error',
                'message' => 'La imagen no existe'
            ];
        }
        //Mostrar el error posible
        return response()->json($data, $data['code']);
    }
    
     public function getPostsByCategory($id){
         $posts = Post::where('category_id', $id)->get();
         
         return response()->json([
             'status' => 'success',
             'posts' => $posts
         ],200);
     }
     
     public function getPostsByUser($id){
         $posts = Post::where('user_id', $id)->get();
         
         return response()->json([
             'status' => 'success',
             'posts' => $posts
                
         ], 200);
     }
}