<?php

namespace App\Http\Controllers;

use App\EmbellishmentItem;
use App\Embellishment;
use App\EmbellishmentMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DataTables;
use Illuminate\Support\Facades\URL;
use Image;
use Intervention\Image\Exception\NotReadableException;

class EmbellishmentItemController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $categories = Embellishment::get();
        return view('embellishment_item.embellishment_item_index',compact('categories'));
    }

    public function embellishmentItemAPI(Request $request)
    {

        if ($request->ajax()) {

            $data = EmbellishmentItem::join('embellishments', 'embellishment_items.embellishment_id', '=', 'embellishments.id')
                ->select(['embellishment_items.*','embellishments.name as cat_name'])
                ->orderBy('embellishment_items.created_at', 'desc');

            return Datatables::of($data)
                ->addIndexColumn()
                ->addColumn('Actions', function ($row) {
                    return '<a class="btn btn-sm btn-clean btn-icon" href="' . URL::to('embellishment_item/' . $row->id . '/edit') .'"><i class="la la-edit"></i></a>'; })
                ->rawColumns(['Actions'])
                ->make(true);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $embellishments = Embellishment::select('id','name')->where('active','1')->get();
        return view('embellishment_item.embellishment_item_add', compact('embellishments'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
//        dd($request->all());
        $this->validate($request, [
            'name' => 'required|unique:embellishment_items,name',
            'emb_item_img_thumb' => 'required:embellishment_items,thumbnail',
            'upload_image' => 'required:embellishment_items,other_images',

        ],
            [
                'name.required' => 'The Embellishment Item name is required',
                'name.unique' => 'The Embellishment Item name has already been taken',
                'emb_item_img_thumb.required' => 'The Embellishment Item tubmbnail is required',
                'upload_image.required' => 'The Embellishment Item other image is required',
            ]);


            if(request('min_qty') < request('max_qty')){
                $images=array();
                if($files=$request->file('upload_image')){
                    foreach($files as $file) {
                        $ImageUpload    = Image::make($file);
                        $thumbnailPath  = public_path('/media');
                        $ImageUpload->resize(420,650);
                        $date = date("dH");
                        $digits = 3;
                        $mdigit = rand(pow(10, $digits-1), pow(10, $digits)-1);
                        $extension = $date.$mdigit.'.'.$file->getClientOriginalExtension();
                        $ImageUpload->save($thumbnailPath.$extension);
                        $images[]       = '/media'.$extension;
                    }
                }

                $embellishmentItem = new EmbellishmentItem();
                $embellishmentItem->name = request('name');
                $embellishmentItem->embellishment_id = request('embellishment_id');
                $embellishmentItem->description = request('description');
                $embellishmentItem->price = request('price');
                $embellishmentItem->special_price = request('special_price');
                $embellishmentItem->min_qty = request('min_qty');
                $embellishmentItem->max_qty = request('max_qty');
                $embellishmentItem->active = request('active');
                $embellishmentItem->other_images = implode("|",$images);
                $embellishmentItem->created_by = Auth::user()->name;

                if($request->has('emb_item_img_thumb')){
                    $file   = $request->file('emb_item_img_thumb');
                    $ImageUpload    = Image::make($file);
                    $thumbnailPath  = public_path('/media');
                    $ImageUpload->resize(170,190);
                    $date = date("dH");
                    $digits = 3;
                    $mdigit = rand(pow(10, $digits-1), pow(10, $digits)-1);
                    $extension = $date.$mdigit.'.'.$file->getClientOriginalExtension();
                    $ImageUpload->save($thumbnailPath.$extension);
                    $embellishmentItem->thumbnail = '/media'.$extension;

                }
                if( $embellishmentItem->save() )
                {

                    return redirect()->route('embellishment_item.index')->with('title','success')->with('message', 'Embellishment Added Successfully');
                }
                else{
                    return redirect()->route('embellishment_item.index')->with('title','error')->with('message', 'Embellishment Not Added Try again Later!....');
                }
            }else{
                return redirect()->back()->withInput($request->all())->with('title','error')->with('message', 'Max value should be greater than min value');
            }


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\EmbellishmentItem  $embellishmentItem
     * @return \Illuminate\Http\Response
     */
    public function show(EmbellishmentItem $embellishmentItem)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\EmbellishmentItem  $embellishmentItem
     * @return \Illuminate\Http\Response
     */
    public function edit(EmbellishmentItem $embellishmentItem)
    {
        // dd($embellishmentItem);
        $embellishments = Embellishment::select('id','name')->where('active','1')->get();
        return view('embellishment_item.embellishment_item_edit', compact('embellishmentItem', 'embellishments'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\EmbellishmentItem  $embellishmentItem
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmbellishmentItem $embellishmentItem)
    {
//        dd($request->all());
        $this->validate($request, [
            'name' => 'required|unique:embellishment_items,name,'.$embellishmentItem->id,
            'other_img_hidden' => 'required:embellishment_items,other_images,'.$embellishmentItem->id,
        ],
            [
                'name.required' => 'The Embellishment Item name is required',
                'name.unique' => 'The Embellishment Item name has already been taken',
                'other_img_hidden.required' => 'Other image is required',
            ]);
        if(request('min_qty') < request('max_qty')){

            $embellishmentItem = EmbellishmentItem::find(request('id'));
            $embellishmentItem->name = request('name');
            $embellishmentItem->description = request('description');
            $embellishmentItem->embellishment_id = request('embellishment_id');
            $embellishmentItem->price = request('price');
            $embellishmentItem->special_price = request('special_price');
            $embellishmentItem->min_qty = request('min_qty');
            $embellishmentItem->max_qty = request('max_qty');



            $other_Images = request('other_img_hidden');
            $images       = array();
            if($files=$request->file('upload_image')){
                foreach($files as $file) {
                    $ImageUpload    = Image::make($file);
                    $thumbnailPath  = public_path('/media/embellishments_media/');
                    $ImageUpload->resize(420,650);
                    $date = date("dH");
                    $digits = 3;
                    $mdigit = rand(pow(10, $digits-1), pow(10, $digits)-1);
                    $extension = $date.$mdigit.'.'.$file->getClientOriginalExtension();
                    $ImageUpload->save($thumbnailPath.$extension);
                    $images[]       = '/media/embellishments_media/'.$extension;
                }
                $result                = array_merge($other_Images, $images);
                $embellishmentItem->other_images = implode("|",$result);
            }
            elseif($other_Images){
                $embellishmentItem->other_images = implode("|",$other_Images);
            }
            else{

                $embellishmentItem->other_images = "";
            }


            if($request->has('emb_item_img_thumb')){
                $file   = $request->file('emb_item_img_thumb');
                $ImageUpload    = Image::make($file);
                $thumbnailPath  = public_path('/media/embellishments_media/');
                $ImageUpload->resize(170,190);
                $date = date("dH");
                $digits = 3;
                $mdigit = rand(pow(10, $digits-1), pow(10, $digits)-1);
                $extension = $date.$mdigit.'.'.$file->getClientOriginalExtension();
                $ImageUpload->save($thumbnailPath.$extension);
                $embellishmentItem->thumbnail = '/media/embellishments_media/'.$extension;
            } else {
                $embellishmentItem->thumbnail = request('emb_item_img_thumb_hidden');
            }

            $embellishmentItem->active = request('active');

            if( $embellishmentItem->save() )
            {

                // return redirect()->route('embellishment_item.index');
                return redirect()->route('embellishment_item.index')->with('title','success')->with('message', 'embellishmentItem Updated Successfully');
            }
            else{
                return redirect()->route('embellishment_item.index')->with('title','error')->with('message', 'embellishmentItem Not Updated Try again Later!....');
            }

        }else{
            return redirect()->back()->withInput($request->all())->with('title','error')->with('message', 'Max value should be greater than min value');
        }


    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\EmbellishmentItem  $embellishmentItem
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmbellishmentItem $embellishmentItem)
    {
        //
    }
}
