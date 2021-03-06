<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Category;
use App\Tag;
use App\Article;

use App\Image;

use Laracasts\Flash\Flash;

class ArticlesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $articles = Article::Search($request)->orderBy('id','DESC')->paginate(5);
        $articles->each(function($articles){
            $articles->category;
            $articles->user;

        });

        return view('admin.articles.index')->with([
            'articles' => $articles,

        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $categories = Category::orderBy('name','ASC')->pluck('name','id');
        //pluck para mostrar listado de lo que le paso
        $tags = Tag::orderBy('name','ASC')->pluck('name','id');
        return view('admin.articles.create')->with([
            'tags' => $tags,
            'categories'=> $categories
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $this->validate($request, [
            'title' => 'required|unique:articles|max:120',
            'category_id' => 'required',
            'content' => 'required|max:240',

            'image' => 'image|required'
        ]);


        //dd($request->tags);
        if($request->file('image')){
            $file = $request->file('image');
            $name = 'blogfacilito_' . time() . '.' . $file->getClientOriginalExtension();//nombre de la imagen
            $path = public_path() . '/images/articles/';//enlace donde piensa guardar las imagenes
            $file->move($path, $name);//movimiento de las imagenes
        }
        
        $article = new Article($request->all());
        //$article->article_id = $request->article_id;
        $article->user_id = \Auth::user()->id;
        //dd($article->all());
        $article->save();


        $article->tags()->sync($request->tags);
        //sync para agregar datos a la tabla pivot


        $image= new Image();
        $image->name = $name;
        $image->article()->associate($article);
        //associate para asociar la llave foranea
        //$image->article_id = $article->id;
        $image->save();

        Flash("Se ah registrado ".$article->title. " con exito")->success();

        return redirect()->route('articles.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {

        $article = Article::find($id);
        $article->category; 
        $categories = Category::orderby('name','DESC')->pluck('name','id');
        $tags = Tag::orderby('name','DESC')->pluck('name','id');

        $my_tags = $article->tags->pluck('id')->ToArray();
        
        //dd($my_tags);
        //ToArray se convierte deobjeto a array

        return view('admin.articles.edit')->with([
            'categories' => $categories,
            'article' => $article,
            'tags' => $tags,
            'my_tags' => $my_tags
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $this->validate($request, [
            'title' => 'required|unique:articles|max:120',
            'category_id' => 'required',
            'content' => 'required|max:240',

            'image' => 'image|required'
        ]);
        $article = Article::find($id);
        $article->fill($request->all());
        //fill para sobreescribir 
        $article->save();

        $article->tags()->sync($request->tags);

        Flash("Se ah editado el articulo ".$article->title. " con exito")->success();
        
            return redirect()->route('articles.index');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $article = Article::find($id);
        $article->delete();

        Flash("Se ah eliminado el articulo ".$article->title. " con exito")->success();
        
            return redirect()->route('articles.index');

    }
}
