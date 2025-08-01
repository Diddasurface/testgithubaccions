<?php
namespace App\Http\Controllers\Tenant;
use App\Helpers\TenantStorage;
use App\Exports\DigemidItemExport;
use App\Exports\ItemExport;
use App\Exports\ItemExportWp;
use App\Exports\ItemExtraDataExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PdfUnionController;
use App\Http\Controllers\SearchItemController;
use App\Http\Requests\Tenant\ItemRequest;
use App\Http\Resources\Tenant\ItemCollection;
use App\Http\Resources\Tenant\ItemResource;
use App\Imports\CatalogImport;
use App\Imports\ItemsImport;
use App\Imports\ItemsImportRestaurant;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\AttributeType;
use App\Models\Tenant\Catalogs\CatColorsItem;
use App\Models\Tenant\Catalogs\CatItemMoldCavity;
use App\Models\Tenant\Catalogs\CatItemMoldProperty;
use App\Models\Tenant\Catalogs\CatItemPackageMeasurement;
use App\Models\Tenant\Catalogs\CatItemProductFamily;
use App\Models\Tenant\Catalogs\CatItemStatus;
use App\Models\Tenant\Catalogs\CatItemUnitBusiness;
use App\Models\Tenant\Catalogs\CatItemUnitsPerPackage;
use App\Models\Tenant\Catalogs\ChargeDiscountType;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\OperationType;
use App\Models\Tenant\Catalogs\PriceType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\Catalogs\Tag;
use App\Models\Tenant\Catalogs\UnitType;
use App\Models\Tenant\CatItemSize;
use App\Models\Tenant\Company;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Item;
use App\Models\Tenant\ItemImage;
use App\Models\Tenant\ItemMovement;
use App\Models\Tenant\ItemSupply;
use App\Models\Tenant\ItemTag;
use App\Models\Tenant\ItemUnitType;
use App\Models\Tenant\ItemWarehousePrice;
use App\Models\Tenant\Warehouse;
use App\Traits\OfflineTrait;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Modules\Account\Models\Account;
use Modules\Digemid\Models\CatDigemid;
use Modules\Finance\Helpers\UploadFileHelper;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Category;
use Modules\Item\Models\ItemLot;
use Modules\Item\Models\ItemLotsGroup;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use setasign\Fpdi\Fpdi;
use Modules\Inventory\Models\InventoryConfiguration;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class ItemController extends Controller
{
    use OfflineTrait;

    public function index()
    {
        $type = 'PRODUCTS';
        return view('tenant.items.index', compact('type'));
    }

    public function indexServices()
    {
        $type = 'ZZ';
        return view('tenant.items.index', compact('type'));
    }

    public function index_ecommerce()
    {
        return view('tenant.items_ecommerce.index');
    }

    public function columns()
    {
        return [
            'description' => 'Nombre',
            'internal_id' => 'Código interno',
            'barcode' => 'Código de barras',
            'model' => 'Modelo',
            'brand' => 'Marca',
            'date_of_due' => 'Fecha vencimiento',
            'lot_code' => 'Código lote',
            'active' => 'Habilitados',
            'inactive' => 'Inhabilitados',
            'category' => 'Categoria'
        ];
    }

    public function records(Request $request)
    {

        // dd($request->all());
        $records = $this->getRecords($request);

        return new ItemCollection($records->paginate(config('tenant.items_per_page')));
    }


    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRecords(Request $request)
    {

        $isEcommerce = filter_var($request->query('isEcommerce'), FILTER_VALIDATE_BOOLEAN);
        // $records = Item::whereTypeUser()->whereNotIsSet();
        $records = $this->getInitialQueryRecords($isEcommerce);

        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($request->column)
        {

            case 'brand':
                $records->whereHas('brand',function($q) use($request){
                                    $q->where('name', 'like', "%{$request->value}%");
                                });
                break;
            case 'category':
                $records->whereHas('category',function($q) use($request){
                                    $q->where('name', 'like', "%{$request->value}%");
                                });
                break;

            case 'active':
                $records->whereIsActive();
                break;

            case 'inactive':
                $records->whereIsNotActive();
                break;

            default:
                if($request->has('column'))
                {
                    if($this->applyAdvancedRecordsSearch() && $request->column === 'description')
                    {
                        if($request->value) $records->whereAdvancedRecordsSearch($request->column, $request->value);
                    }
                    else
                    {
                        $records->where($request->column, 'like', "%{$request->value}%");
                    }
                }
                break;
        }

        if ($request->has('show_disabled')) {
            switch ($request->show_disabled) {
                case 'enabled':
                    $records->where('active', 1);
                    break;
                case 'disabled':
                    $records->where('active', 0);
                    break;
                // no hacer nada si es 'all'
            }
        }
        if ($request->type) {
            if($request->type ==='PRODUCTS') {
                // listar solo productos en la lista de productos
                $records->whereNotService();
            }else{
                $records->whereService();
            }
        }
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $records->Pharmacy()
                ->with(['cat_digemid']);
        }

        $isRestaurant = $request->has('isRestaurant') && $request->isRestaurant === 'true';
        $isEcommerce = $request->has('isEcommerce') && $request->isEcommerce === 'true';
        
        if ($request->has('list_value')) {
            switch ($request->list_value) {
                case 'visible':
                    if ($isRestaurant) {
                        $records->where('apply_restaurant', 1);
                    }
                    if ($isEcommerce) {
                        $records->where('apply_store', 1);
                    }
                    break;
        
                case 'hidden':
                    if ($isRestaurant) {
                        $records->where('apply_restaurant', 0);
                    }
                    if ($isEcommerce) {
                        $records->where('apply_store', 0);
                    }
                    break;
            }
        }


        return $records->orderBy($sortField, $sortDirection);

    }


    /**
     *
     * Aplicar filtros iniciales a la consulta
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getInitialQueryRecords($isEcommerce)
    {

        if(Configuration::getRecordIndividualColumn('list_items_by_warehouse') && !$isEcommerce)
        {
            $records = Item::whereWarehouse()->whereNotIsSet();
        }
        else
        {
            $records = Item::whereTypeUser()->whereNotIsSet();
        }

        return $records;
    }


    public function create()
    {
        return view('tenant.items.form');
    }

    public function tables()
    {
        $unit_types = UnitType::whereActive()->orderByDescription()->get();
        $currency_types = CurrencyType::whereActive()->orderByDescription()->get();
        $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $system_isc_types = SystemIscType::whereActive()->orderByDescription()->get();
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $warehouses = Warehouse::all();
        $accounts = Account::all();
        $tags = Tag::all();
        $categories = Category::all();
        $brands = Brand::all();
        $configuration= Configuration::first();
        /** Informacion adicional */
        $colors = collect([]);
        $CatItemStatus=$colors;
        $CatItemUnitBusiness = $colors;
        $CatItemMoldCavity = $colors;
        $CatItemPackageMeasurement =$colors;
        $CatItemUnitsPerPackage = $colors;
        $CatItemMoldProperty = $colors;
        $CatItemProductFamily= $colors;
        $CatItemSize= $colors;
        if($configuration->isShowExtraInfoToItem()){
            $colors = CatColorsItem::all();
            $CatItemStatus= CatItemStatus::all();
            $CatItemSize= CatItemSize::all();
            $CatItemUnitBusiness = CatItemUnitBusiness::all();
            $CatItemMoldCavity = CatItemMoldCavity::all();
            $CatItemPackageMeasurement = CatItemPackageMeasurement::all();
            $CatItemUnitsPerPackage = CatItemUnitsPerPackage::all();
            $CatItemMoldProperty = CatItemMoldProperty::all();
            $CatItemProductFamily= CatItemProductFamily::all();
        }
        /** Informacion adicional */
        $configuration = $configuration->getCollectionData();
        $inventory_configuration = InventoryConfiguration::firstOrFail();
        /*
        $configuration = Configuration::select(
            'affectation_igv_type_id',
            'is_pharmacy',
            'show_extra_info_to_item'
        )->firstOrFail();
        */
        return compact(
            'unit_types',
            'currency_types',
            'attribute_types',
            'system_isc_types',
            'affectation_igv_types',
            'warehouses',
            'accounts',
            'tags',
            'categories',
            'brands',
            'configuration',
            'colors',
            'CatItemSize',
            'CatItemMoldCavity',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemProductFamily',
            'CatItemUnitsPerPackage',
            'inventory_configuration'
        );
    }

    public function record($id)
    {
        $record = new ItemResource(Item::findOrFail($id));

        return $record;
    }

    public function store(ItemRequest $request) {


        $id = $request->input('id');
        if (!$request->barcode) {
            if ($request->internal_id) {
                $request->merge(['barcode' => $request->internal_id]);
            }
        }
        $item = Item::firstOrNew(['id' => $id]);
        $item->item_type_id = '01';
        $item->amount_plastic_bag_taxes = Configuration::firstOrFail()->amount_plastic_bag_taxes;
        if ($request->has('date_of_due')) {
            $time = $request->date_of_due;
            $date = null;
            if (isset($time['date'])) {
                $date = $time['date'];
                if (!empty($date)) {
                    $request->merge(['date_of_due' => Carbon::createFromFormat('Y-m-d H:i:s.u', $date)]);
                }
            }
        }
        $current_lot = null;
        if(!empty($item->id)){
            $current_lot = ItemLotsGroup::where([
                'code' => $item->lot_code,
                'item_id'=>$item->id
            ])->first();
        }

        $item->fill($request->all());

        $temp_path = $request->input('temp_path');
        if($temp_path) {

            $directory = 'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR;

            $slug_name = Str::slug($item->description);
            if($item->internal_id){
                $slug_name = Str::slug($item->internal_id);
            }
            $prefix_name = Str::limit($slug_name, 20, '');

            $file_name_old = $request->input('image');
            $file_name_old_array = explode('.', $file_name_old);
            $file_content = file_get_contents($temp_path);
            $datenow = date('YmdHis');
            $file_name = $prefix_name.'-'.$datenow.'.'.$file_name_old_array[1];

            UploadFileHelper::checkIfValidFile($file_name, $temp_path, true);

            Storage::put($directory.$file_name, $file_content);
            $item->image = $file_name;

            //--- IMAGE SIZE MEDIUM
            $image = \Image::make($temp_path);
            $file_name = $prefix_name.'-'.$datenow.'_medium'.'.'.$file_name_old_array[1];
            $image->resize(512, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($directory.$file_name,  (string) $image->encode('jpg', 30));
            $item->image_medium = $file_name;

              //--- IMAGE SIZE SMALL
            $image = \Image::make($temp_path);
            $file_name = $prefix_name.'-'.$datenow.'_small'.'.'.$file_name_old_array[1];
            $image->resize(256, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            Storage::put($directory.$file_name,  (string) $image->encode('jpg', 20));
            $item->image_small = $file_name;



        }else if(!$request->input('image') && !$request->input('temp_path') && !$request->input('image_url')){
            $item->image = 'imagen-no-disponible.jpg';
        }

        $item->save();

        foreach ($request->item_unit_types as $value) {

            $item_unit_type = ItemUnitType::firstOrNew(['id' => $value['id']]);
            $item_unit_type->item_id = $item->id;
            $item_unit_type->description = $value['description'];
            $item_unit_type->unit_type_id = $value['unit_type_id'];
            $item_unit_type->quantity_unit = $value['quantity_unit'];
            $item_unit_type->price1 = $value['price1'];
            $item_unit_type->price2 = $value['price2'];
            $item_unit_type->price3 = $value['price3'];
            $item_unit_type->price_default = $value['price_default'];
            $item_unit_type->save();

            // migracion desarrollo sin terminar #1401
            if(!$value['barcode']) {
                $item_unit_type->barcode = $item_unit_type->id.$item_unit_type->unit_type_id.$item_unit_type->quantity_unit;
                $item_unit_type->save();
            }
            else {
                $item_unit_type->barcode = $value['barcode'];
                $item_unit_type->save();
            }
        }
        if (isset($request->supplies)) {
            foreach($request->supplies as $value){

                if(!isset($value['item_id'])) $value['item_id'] = $item->id;
                $itemSupply = ItemSupply::firstOrCreate(['id' => $value['id']],$value);
                $itemSupply->fill($value);
                $itemSupply->save();
            }
        }

        $configuration = Configuration::first();
        if($configuration->isShowExtraInfoToItem()){
            // Extra data
            if($request->has('colors')){
                $item->setItemColor($request->colors);
            }
            if($request->has('CatItemUnitsPerPackage')){
                $item->setItemUnitsPerPackage($request->CatItemUnitsPerPackage);
            }
            if($request->has('CatItemMoldCavity')){
                $item->setItemMoldCavity($request->CatItemMoldCavity);
            }
            if($request->has('CatItemMoldProperty')){
                $item->setItemMoldProperty($request->CatItemMoldProperty);
            }
            if($request->has('CatItemUnitBusiness')){
                $item->setItemUnitBusiness($request->CatItemUnitBusiness);
            }
            if($request->has('CatItemStatus')){
                $item->setItemStatus($request->CatItemStatus);
            }
            if($request->has('CatItemPackageMeasurement')){
                $item->setItemPackageMeasurement($request->CatItemPackageMeasurement);
            }
            if($request->has('CatItemProductFamily')){
                $item->setItemProductFamily($request->CatItemProductFamily);
            }
            if($request->has('CatItemSize')){
                $item->setItemSize($request->CatItemSize);
            }
            // Extra data
        }



        if ($request->tags_id) {
            ItemTag::destroy(   ItemTag::where('item_id', $item->id)->pluck('id'));
            foreach ($request->tags_id as $value) {
                ItemTag::create(['item_id' => $item->id,  'tag_id' => $value]);
                //$tag = ItemTag::where('item_id', $item->id)->where('tag_id', $value)->first();
            }
        }

        if (!$id) {

            // $item->lots()->delete();
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();

            //$warehouse = WarehouseModule::find(auth()->user()->establishment_id);
            if($warehouse && !isset($request->warehouse_id)){
                $item->warehouse_id = $warehouse->id;
                $item->save();
            }

            $v_lots = isset($request->lots) ? $request->lots:[];

            foreach ($v_lots as $lot) {
                $item->lots()->create([
                    'date' => $lot['date'],
                    'series' => $lot['series'],
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse ? $warehouse->id:null,
                    'has_sale' => false,
                    'state' => $lot['state'],
                ]);
            }
            $lots_enabled = isset($request->lots_enabled) ? $request->lots_enabled:false;
            $stock = (int)$request->stock;

            if ($lots_enabled && $stock > 0) {
                ItemLotsGroup::create([
                    'code'  => $request->lot_code,
                    'quantity'  => $request->stock,
                    'date_of_due'  => $request->date_of_due,
                    'item_id' => $item->id
                ]);
            }
        } else {
            /*
            $item->lots()->delete();
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();
            $v_lots = isset($request->lots) ? $request->lots:[];
            foreach ($v_lots as $lot) {
                if ($lot['deleted'] == true) {
                    ItemLot::find($lot['id'])->delete();
                } else {
                    if ( isset( $lot['id'] )) {
                        ItemLot::find($lot['id'])->update([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'state' => $lot['state'],
                        ]);
                    } else {
                        $item->lots()->create([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse ? $warehouse->id:null,
                            'has_sale' => false,
                            'state' => $lot['state'],
                        ]);
                    }
                }
            }
            */
            /****************************** SECCION PARA SEIRES EN ITEMLOT **********************************************/
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();
            $v_lots = isset($request->lots) ? $request->lots:[];
            foreach ($v_lots as $lot) {
                /**
                 * @var  ItemLot $temp_serie
                 * @var Int $lot_id
                 * @var Bool $delete
                 */
                $lot_id = isset($lot['id'])? (int) $lot['id']:0;
                $delete = isset($lot['deleted'])?(boolean)$lot['deleted']:false;
                if($lot_id != 0){
                    $temp_serie = ItemLot::find($lot_id);
                    if(!empty($temp_serie)){
                        if($delete == true){
                            $temp_serie->delete();
                        }else{
                            $temp_serie
                                ->setDate($lot['date'])
                                ->setSeries($lot['series'])
                                ->setState($lot['state'])
                                ->push();
                        }
                    }
                }else{
                    $temp_serie = new ItemLot([
                        'date' => $lot['date'],
                        'series' => $lot['series'],
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse ? $warehouse->id:null,
                        'has_sale' => false,
                        'state' => $lot['state'],
                    ]);
                    $temp_serie->push();
                }
            }

            $lots_enabled = isset($request->lots_enabled) ? $request->lots_enabled:false;
            /****************************** SECCION PARA LOTE EN ITEM LOT_CODE ******************************************/
            if ($lots_enabled and !empty($request->lot_code)) {
                if(empty($current_lot)){
                    $current_lot = new ItemLotsGroup([
                        'code' => $item->lot_code,
                        'item_id'=>$item->id,
                        'quantity' => $request->stock,
                         'date_of_due'=>$request->date_of_due,
                    ]);
                    $current_lot->push();
                }else{
                    $lotes = ItemLotsGroup::where([
                        'code'=>$current_lot->code,
                        // 'quantity',
                        // 'date_of_due',
                        'item_id'=>$item->id
                    ])->get();
                    /** @var ItemLotsGroup $lot */
                    foreach($lotes as $lot){
                        $lot
                            ->setCode($request->lot_code)
                            ->setDateOfDue($request->date_of_due)
                            ->push();
                    }
                }
                /*
                 ItemLotsGroup::where('item_id', $item->id)->delete();
                ItemLotsGroup::create([
                    'code'  => $request->lot_code,
                    'quantity'  => $request->stock,
                    'date_of_due'  => $request->date_of_due,
                    'item_id' => $item->id
                ]);
                */
            }
        }

        $directory = 'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR;

        $multi_images = isset($request->multi_images) ? $request->multi_images:[];

        foreach ($multi_images as $im) {

            $file_name = $im['filename'];
            UploadFileHelper::checkIfValidFile($file_name, $im['temp_path'], true);

            $file_content = file_get_contents($im['temp_path']);
            Storage::put($directory.$file_name, $file_content);

            ItemImage::create(['item_id'=> $item->id, 'image' => $file_name]);
        }

        if (!$item->barcode) {
            $item->barcode = str_pad($item->id, 12, '0', STR_PAD_LEFT);
        }

        $item->update();

        // migracion desarrollo sin terminar #1401
        // $inventory_configuration = InventoryConfiguration::firstOrFail();

        // if($inventory_configuration->generate_internal_id == 1) {
        //     if(!$item->internal_id) {
        //         $items = Item::count();
        //         $item->internal_id = (string)($items + 1);
        //         $item->save();
        //     }
        // }

        $this->generateInternalId($item);

        /********************************* SECCION PARA PRECIO POR ALMACENES ******************************************/

        // Precios por almacenes
        // $warehouses = $request->warehouses;

        $this->createItemWarehousePrices($request, $item);

        // if ($warehouses) {
            // /** @var ItemWarehousePrice $price */

            // foreach ($warehouses as $warehouse) {
            //     $price = ItemWarehousePrice::where([
            //         'item_id' => $item->id,
            //         'warehouse_id' => $warehouse['id'],
            //     ])->first();
            //     if(empty($price)){
            //         $price = new ItemWarehousePrice([
            //             'item_id' => $item->id,
            //             'warehouse_id' => $warehouse['id'],
            //         ]) ;
            //     }
            //     $price
            //         ->setPrice($warehouse['price'])
            //         ->push();
            // }

            /*
            ItemWarehousePrice::where('item_id', $item->id)
                ->delete();

            foreach ($warehouses as $warehousePrice) {
                try {
                    $price = $warehousePrice['price'];
					if (is_numeric($warehousePrice['price'])) {
						ItemWarehousePrice::query()->insert([
							'item_id'      => $item->id,
							'warehouse_id' => $warehousePrice['id'],
							'price'        => $price,
						]);
					}
                } catch (\Throwable $th) {
                    \Log::error('No se pudo agregar el precio del producto al almacén ' . $warehousePrice['id']);
                }
            }
            */
        // }

        if ($id) {
            Cache::forget("item_{$id}");
            Log::info('Caché eliminada para el ítem actualizado:', ['item_id' => $id]);
        }

        return [
            'success' => true,
            'message' => ($id)?'Producto editado con éxito':'Producto registrado con éxito',
            'id' => $item->id
        ];
    }

    public function visibleMassive(Request $request)
    {
        $type_product = $request->input('resource');
        $column = $type_product === 'restaurant' ? 'apply_restaurant' : 'apply_store';

        try {
            Item::whereNotNull('internal_id')->where($column, '=', 0)->update([
                $column => true
            ]);
            return [
                'success' => true,
                'message' => 'Todo los productos son visible en el restaurante'
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage()
            ];
        }

    }
    /**
     *
     * Generar codigo interno de forma automatica
     *
     * @param  Item $item
     * @return void
     */
    public function generateInternalId(Item &$item)
    {
        $inventory_configuration = InventoryConfiguration::select('generate_internal_id')->firstOrFail();

        if($inventory_configuration->generate_internal_id && !$item->internal_id)
        {
            $item->internal_id = str_pad($item->id, 5, '0', STR_PAD_LEFT);
            $item->save();
        }
    }



    /**
     * @param ItemRequest|null $request
     * @param null $item
     * @throws Exception
     */
    private function createItemWarehousePrices(ItemRequest $request = null, Item $item = null)
    {
        if ($request !== null && $request->has('item_warehouse_prices') && $item !== null) {
            foreach ($request->item_warehouse_prices as $item_warehouse_price) {
                if ($item_warehouse_price['price'] && $item_warehouse_price['price'] != '') {
                    ItemWarehousePrice::updateOrCreate([
                        'item_id' => $item->id,
                        'warehouse_id' => $item_warehouse_price['warehouse_id'],
                    ], [
                        'price' => $item_warehouse_price['price'],
                    ]);
                } else {
                    if ($item_warehouse_price['id']) {
                        ItemWarehousePrice::findOrFail($item_warehouse_price['id'])->delete();
                    }
                }
            }
        }
    }


    /**
     * Eliminar item
     *
     * Usado en:
     * Modules\MobileApp\Http\Controllers\Api\ItemController
     *
     * @param  int $id
     * @return array
     *
     */
    public function destroy($id)
    {
        try {

            $item = Item::findOrFail($id);
            $this->deleteRecordInitialKardex($item);
            $this->deleteRecordInitialWeightedCosts($item);
            $item->delete();

            return [
                'success' => true,
                'message' => 'Producto eliminado con éxito'
            ];

        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false,'message' => 'El producto esta siendo usado por otros registros, no puede eliminar'] : ['success' => false,'message' => 'Error inesperado, no se pudo eliminar el producto'];

        }


    }

    public function destroyMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemDeleted = 0;
        $count = $selected->count();


        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemDeleted){
            $response = $this->destroy($id);
            if ($response['success']) $itemDeleted += 1;
        });

        return [
            'success' => true,
            'message' => "Se eliminaron {$itemDeleted} productos de {$count} productos seleccionados"
        ];

    }



    public function destroyItemUnitType($id)
    {
        $item_unit_type = ItemUnitType::findOrFail($id);
        $item_unit_type->delete();

        return [
            'success' => true,
            'message' => 'Registro eliminado con éxito'
        ];
    }


    public function import(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $import = new ItemsImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function importRestaurant(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $import = new ItemsImportRestaurant();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function catalog(Request $request)
    {
        $request->validate([
            'catalog_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $old_digemid = CatDigemid::setInactiveMassive();
                $import = new CatalogImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $updated  = $import->getUpdated();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => count($updated),
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function upload(Request $request)
    {

        $validate_upload = UploadFileHelper::validateUploadFile($request, 'file', 'jpg,jpeg,png,gif,svg');

        if(!$validate_upload['success']){
            return $validate_upload;
        }

        if ($request->hasFile('file')) {
            $new_request = [
                'file' => $request->file('file'),
                'type' => $request->input('type'),
            ];

            return $this->upload_image($new_request);
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    function upload_image($request)
    {
        $file = $request['file'];
        $type = $request['type'];

        $temp = tempnam(sys_get_temp_dir(), $type);
        file_put_contents($temp, file_get_contents($file));

        $mime = mime_content_type($temp);
        $data = file_get_contents($temp);

        return [
            'success' => true,
            'data' => [
                'filename' => $file->getClientOriginalName(),
                'temp_path' => $temp,
                'temp_image' => 'data:' . $mime . ';base64,' . base64_encode($data)
            ]
        ];
    }

    private function deleteRecordInitialKardex($item){

        if($item->kardex->count() == 1){
            ($item->kardex[0]->type == null) ? $item->kardex[0]->delete() : false;
        }

    }


    /**
     *
     * @param  Item $item
     * @return void
     */
    private function deleteRecordInitialWeightedCosts($item)
    {
        if($item->weighted_average_costs()->count() == 1)
        {
            $item->weighted_average_cost()->delete();
        }
    }


    public function visibleStore(Request $request)
    {
        $item = Item::find($request->id);

        if(!$item->internal_id && $request->apply_store){
            return [
                'success' => false,
                'message' =>'Para habilitar la visibilidad, debe asignar un codigo interno al producto',
            ];
        }

        $visible = $request->apply_store == true ? 1 : 0 ;
        $item->apply_store = $visible;
        $item->save();

        return [
            'success' => true,
            'message' => ($visible > 0 )?'El Producto ya es visible en tienda virtual' : 'El Producto ya no es visible en tienda virtual',
            'id' => $request->id
        ];

    }

    public function duplicate(Request $request)
    {
        // return $request->id;
        $obj = Item::find($request->id);

        if($obj->lots_enabled){
            $obj->date_of_due = null;
            $obj->lot_code = null;
            $obj->stock = 0;
        }

        $new = $obj->setDescription($obj->getDescription().' (Duplicado)')->replicate();
        $new->save();

        return [
            'success' => true,
            'data' => [
                'id' => $new->id,
            ],
        ];

    }

    public function disable($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->active = 0;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto inhabilitado con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo inhabilitar el producto'];

        }
    }

    public function disableMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemDisabled = 0;
        $count = $selected->count();


        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemDisabled){
            $response = $this->disable($id);
            if ($response['success']) $itemDisabled += 1;
        });

        return [
            'success' => true,
            'message' => "Se inhabilitaron {$itemDisabled} productos de {$count} productos seleccionados"
        ];

    }

    public function images($item)
    {
        $records = ItemImage::where('item_id', $item)->get()->transform(function($row){
            return [
                'id' => $row->id,
                'item_id' => $row->item_id,
                'image' => $row->image,
                'name' => $row->image,
                'url'=> asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image)
            ];
        });
        return [
            'success' => true,
            'data' => $records
        ];
    }

    public function delete_images($id)
    {
        $record = ItemImage::findOrFail($id);
        $record->delete();

        return [
            'success' => true,
            'message' => 'Imagen eliminada con éxito'
        ];
    }


    public function enable($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->active = 1;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto habilitado con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo habilitar el producto'];

        }
    }

    public function enableMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemEnable = 0;
        $count = $selected->count();

        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemEnable){
            $response = $this->enable($id);
            if ($response['success']) $itemEnable += 1;
        });

        return [
            'success' => true,
            'message' => "Se habilitaron {$itemEnable} productos de {$count} productos seleccionados"
        ];

    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $d_start = null;
        $d_end = null;
        $period = $request->period;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($request->month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($request->month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
        }

        // $date = $request->month_start.'-01';
        // $start_date = Carbon::parse($date);
        // $end_date = Carbon::parse($date)->addMonth()->subDay();

        $items = Item::whereTypeUser()->whereNotIsSet();
        $extradata = [];
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $items->Pharmacy();
        }

        if($period !== 'all'){
            $items->whereBetween('items.created_at', [$d_start, $d_end]);
        }

        $records =  $items->get();
        return (new ItemExport())
            ->setExtraData($extradata)
            ->records($records)
            ->download('Reporte_Items_'.Carbon::now().'.xlsx');

    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportWp(Request $request) {
        $date = $request->month_start.'-01';
        $start_date = Carbon::parse($date);
        $end_date = Carbon::parse($date)->addMonth()->subDay();

        $records = Item::whereBetween('created_at', [$start_date, $end_date]);
        $extradata = [];
        $isPharmacy = $request->isPharmacy == 'true' ? true : false;
        if ($request->has('isPharmacy') && $isPharmacy == true) {
            $extradata[] = 'sanitary';
            $extradata[] = 'cod_digemid';
            $records->Pharmacy();
        }
        $records = $records->get();
        return (new ItemExportWp())
            ->setExtraData($extradata)
            ->records($records)
            ->download('Reporte_Items_'.Carbon::now().'.csv', Excel::CSV);

    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadExtraDataPdf(Request $request){
        $field ='';
        $records = $this->exportExtraItem($request,$field);


        $pdf = PDF::loadView('tenant.items.exports.items_extra_data',
            compact("records", "field"))
            ->setPaper('a4', 'landscape');

        $filename = 'Reporte_Items_Extra_Data_'.Carbon::now().'.xlsx';

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadExtraDataItemsExcel(Request $request){
        $field ='';
        $items = $this->exportExtraItem($request,$field);
        $excel = new ItemExtraDataExport();
        $excel->setRecords($items)->setField($field);
        $filename = 'Reporte_Items_Extra_Data_'.Carbon::now().'.xlsx';

        return $excel->download($filename);
        return $excel->view();

    }

    /**
     * Obtiene lo smovimientos de inventario para la categoria correspondiente,
     * se implementa en pdf y excel por igual
     *
     * @param Request $request
     * @param         $field
     *
     * @return Item[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function exportExtraItem(Request $request, &$field){

        $stockByAttribute = ItemMovement::getQueryToStockWithOutItemId(auth()->user()->establishment_id)->distinct();
        $field = $request->fields ?? '';
        if($field == 'colors'){
            $stockByAttribute->where('item_movement_rel_extra.item_color_id','!=',0);
        }elseif($field == 'CatItemMoldProperty'){
            $stockByAttribute->where('item_movement_rel_extra.item_mold_properties_id','!=',0);
        }elseif($field == 'CatItemUnitBusiness'){
            $stockByAttribute->where('item_movement_rel_extra.item_unit_business_id','!=',0);
        }elseif($field == 'CatItemStatus'){
            $stockByAttribute->where('item_movement_rel_extra.item_status_id','!=',0);
        }
        elseif($field == 'CatItemPackageMeasurement'){
            $stockByAttribute->where('item_movement_rel_extra.item_package_measurements_id','!=',0);
        }
        elseif($field == 'CatItemProductFamily'){
            $stockByAttribute->where('item_movement_rel_extra.item_product_family_id','!=',0);
        }
        elseif($field == 'CatItemSize'){
            $stockByAttribute->where('item_movement_rel_extra.item_size_id','!=',0);
        }
        elseif($field == 'CatItemUnitsPerPackage'){
            $stockByAttribute->where('item_movement_rel_extra.item_units_per_package_id','!=',0);
        }
        elseif($field == 'CatItemMoldCavity'){
            $stockByAttribute->where('item_movement_rel_extra.item_mold_cavities_id','!=',0);
        }
        $itemsIds =$stockByAttribute->get()->pluck('item_id')->unique();
        $items = Item::wherein('id',$itemsIds)->get()->transform(function (Item $row){
           return $row->getCollectionData();
        });
        return $items;

    }
    public function exportBarCode(Request $request){

        ini_set("pcre.backtrack_limit", "50000000");

        $start = $request[0];
        $end = $request[1];

        $records = Item::whereBetween('id', [$start, $end]);
        $extradata = [];
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $records->Pharmacy();
        }
        $extra_data = $extradata;
        $records = $records->get();
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [
                104.1,
                101.6
            ],
            'margin_top' => 2,
            'margin_right' => 2,
            'margin_bottom' => 0,
            'margin_left' => 2
        ]);
        $html = view('tenant.items.exports.items-barcode', compact('records','extra_data'))->render();

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_'.now()->format('Y_m_d').'.pdf', 'I');
    }

    /**
     * Genera los codigos de barra por archivo para los items que tengan internal_id o barcode
     * Se prioriza barcode, sino se genera internal_id
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Mpdf\MpdfException
     * @throws \Throwable
     */
    public function exportBarCodeFull(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");

        $start = $request[0];
        $end = $request[1];

        $records = Item::whereBetween('id', [$start, $end])
            ->where(function($q){
                $q->orwhere('barcode','!=','');
                $q->orwhere('internal_id','!=','');
            })
            // ->wherenotnull('barcode')
        ;
        $extradata = [];
        $establishment = \Auth::user()->establishment;
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $records->Pharmacy();
        }
        $extra_data = $extradata;
        $records = $records->get();
        $height = 30;

        $width = 48;
        $pdfj = new Fpdi();
        /** @var Item $item */
        foreach($records as $item){
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    $height
                ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
            $html = view('tenant.items.exports.items-barcode-full', compact('item','extra_data','establishment'))->render();
            $pdf->AddPage();
            $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
            PdfUnionController::addFpi($pdfj, $pdf);
        }

        return PdfUnionController::ResponseAsFile($pdfj,'bar_code_full');

    }
    /**
     * Exporta items al formato de DIGEMID
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportDigemid(Request $request)
    {
        ini_set('max_execution_time', 0);
        $company = Company::first();
        $company_cod_digemid = $company->cod_digemid;
        $records = CatDigemid::where('active',1);
        $max_prices = $records->max('max_prices');
            $records = $records->get();
        $export = new DigemidItemExport();
        $export->setRecords($records)->setCompanyCodDigemid($company_cod_digemid)->setMaxPrice($max_prices);

        return $export->download('Reporte_Items_Digemid_'.Carbon::now().'.xlsx');
    }

    public function printBarCode(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");
        $id = $request->id;

        $record = Item::find($id);
        $item_warehouse = ItemWarehouse::where([['item_id', $id], ['warehouse_id', auth()->user()
            ->establishment->warehouse->id]])->first();

        if(!$item_warehouse){
            return [
                'success' => false,
                'message' => "El producto seleccionado no esta disponible en su almacen!"
            ];
        }

        if($item_warehouse->stock < 1){
            return [
                'success' => false,
                'message' => "El producto seleccionado no tiene stock disponible en su almacen, no puede generar etiquetas!"
            ];
        }

        $stock = $item_warehouse->stock;

        $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    104.1,
                    24
                    ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
        $html = view('tenant.items.exports.items-barcode-id', compact('record', 'stock'))->render();

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_'.now()->format('Y_m_d').'.pdf', 'I');

    }

    public function printBarCodeX(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");
        $id = $request->input('id');
        $format = $request->input('format');

        $record = Item::find($id);
        $item_warehouse = ItemWarehouse::where([['item_id', $id], ['warehouse_id', auth()->user()
            ->establishment->warehouse->id]])->first();

        if(!$item_warehouse){
            return [
                'success' => false,
                'message' => "El producto seleccionado no esta disponible en su almacen!"
            ];
        }

        if($item_warehouse->stock < 1){
            return [
                'success' => false,
                'message' => "El producto seleccionado no tiene stock disponible en su almacen, no puede generar etiquetas!"
            ];
        }

        $stock = $item_warehouse->stock;

        $width = ($format == 1) ? 84 : 104.1;
        $height = ($format == 1) ? 30 : 28;

        $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    $height
                    ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
        $html = view('tenant.items.exports.items-barcode-x', compact('record', 'stock', 'format'))->render();

        // return $html;

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_1x'.$format.'_'.now()->format('Y_m_d').'.pdf', 'I');

    }
    /*---------------------------------------------------------------------------------------------------------------------- */
    public function clicNuevoImprimir(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");
        $id = $request->input('id');
        $format = (int) $request->input('format');
        $barcode_width = $request->input('barcode_width'); // valor por defecto
        $barcode_height = $request->input('barcode_height');
        $columns = $request->input('columns');
        $pdf_size = $request->input('pdf_size', null); // si lo usas

        $has_Name = $request->input('has_Name', true);
        $has_Model = $request->input('has_Model', true);
        $has_Codin = $request->input('has_Codin', true);
       
        $record = Item::find($id);
        $item_warehouse = ItemWarehouse::where([['item_id', $id], ['warehouse_id', auth()->user()
            ->establishment->warehouse->id]])->first();

        if(!$item_warehouse){
            return [
                'success' => false,
                'message' => "El producto seleccionado no esta disponible en su almacen!"
            ];
        }

        if($item_warehouse->stock < 1){
            return [
                'success' => false,
                'message' => "El producto seleccionado no tiene stock disponible en su almacen, no puede generar etiquetas!"
            ];
        }

        $stock = $item_warehouse->stock;

        $width = ($format == 1) ? 84 : 104.1;
        $height = ($format == 1) ? 30 : 28;

        $width = $pdf_size;
        
        $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    $height
                    ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
      $html = view('tenant.items.exports.items-barcode-x', compact('record', 'stock', 'format', 'barcode_width', 'barcode_height', 
            'columns', 
            'pdf_size', 
            'has_Name',
            'has_Model',
            'has_Codin'))->render();

        // return $html;

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_1x'.$format.'_'.now()->format('Y_m_d').'.pdf', 'I');

    }
    public function getBarcodeConfig($columns)
    {
    $path = "format_{$columns}.json";
  //$path = storage_path("app/tenancy/tenants/tenancy_demo/format_{$columns}.json");

    /*
    if (Storage::disk('local')->exists($path)) {
        $content = Storage::disk('local')->get($path);
        $config = json_decode($content, true); */ 
        if (TenantStorage::exists($path)) {
        $content = TenantStorage::get($path);
        $config = json_decode($content, true);

        return response()->json([
            'success' => true,
            'config' => $config
        ]);
    }
    
    return response()->json(['success' => false]);
    }

    public function saveBarcodeConfig(Request $request, $columns)
    {
    $path = "format_{$columns}.json";

    $config = [
        'barcode_height' => $request->input('barcode_height'),
        'barcode_width' => $request->input('barcode_width'),
        'columns' => (int) $columns,
        'pdf_size' => $request->input('tamañopdf'),

        'has_Name' => $request->input('has_Name', true),
        'has_Model' => $request->input('has_Model', true),
        'has_Codin' => $request->input('has_Codin', true),
    ];

   TenantStorage::put($path, json_encode($config, JSON_PRETTY_PRINT));

    return response()->json(['success' => true]);
    }
    /*---------------------------------------------------------------------------------------------------------------------- */
    public function itemLast()
    {
        $record = Item::latest()->first();
        return json_encode(['data' => $record->id]);
    }

    public function tablesImport()
    {
        $user = auth()->user();
        $warehouses = Warehouse::select('id', 'description');
        if ($user->type !== 'admin') {
            $warehouses = $warehouses->where('id', $user->establishment_id);
        }

        return response()->json([
            'warehouses' => $warehouses->get(),
        ], 200);
    }

    /**
     * Obtiene una lista de items del sistema
     *
     * @param \Illuminate\Http\Request $r
     *
     * @return \App\Http\Resources\Tenant\ItemCollection
     */
    public function getAllItems(Request $r){
        $records = $this->getRecords($r);
        return new ItemCollection($records->paginate(5000));

    }


    public function searchItemById($id)
    {
        // $items = SearchItemController::searchByIdToModal($id);
        $items = SearchItemController::getItemsToSupply(null, $id);
        return compact('items');
    }


    public function searchItems(Request $request)
    {

        $items = SearchItemController::getItemsToSupply($request);

        return compact('items');

    }

    public function item_tables()
    {
        // $items = $this->table('items');
        $items = SearchItemController::getItemsToDocuments();
        $categories = [];
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $system_isc_types = SystemIscType::whereActive()->get();
        $price_types = PriceType::whereActive()->get();
        $operation_types = OperationType::whereActive()->get();
        $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $is_client = $this->getIsClient();

        $configuration= Configuration::first();

        /** Informacion adicional */
        $colors = collect([]);
        $CatItemSize=$colors;
        $CatItemStatus=$colors;
        $CatItemUnitBusiness = $colors;
        $CatItemMoldCavity = $colors;
        $CatItemPackageMeasurement =$colors;
        $CatItemUnitsPerPackage = $colors;
        $CatItemMoldProperty = $colors;
        $CatItemProductFamily= $colors;
        if($configuration->isShowExtraInfoToItem()){

            $colors = CatColorsItem::all();
            $CatItemSize= CatItemSize::all();
            $CatItemStatus= CatItemStatus::all();
            $CatItemUnitBusiness = CatItemUnitBusiness::all();
            $CatItemMoldCavity = CatItemMoldCavity::all();
            $CatItemPackageMeasurement = CatItemPackageMeasurement::all();
            $CatItemUnitsPerPackage = CatItemUnitsPerPackage::all();
            $CatItemMoldProperty = CatItemMoldProperty::all();
            $CatItemProductFamily= CatItemProductFamily::all();
        }


        /** Informacion adicional */

        return compact(
            'items',
            'categories',
            'affectation_igv_types',
            'system_isc_types',
            'price_types',
            'operation_types',
            'discount_types',
            'charge_types',
            'attribute_types',
            'is_client',
            'colors',
            'CatItemSize',
            'CatItemMoldCavity',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemProductFamily',
            'CatItemUnitsPerPackage');
    }

    public function exportTxtBartender(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");

        $items = $request->items;
        $columns = $request->columns;

        $columnSelected = $this->getColumnsToBartender($columns);
        $columnsKey = array_keys($columnSelected);

        $itemCollect = collect($items)->map(function($item){

            if(sizeof($item['size']) > 0){
                $sizes = CatItemSize::whereIn('id',$item['size'])->get();
                $item['size'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['size'] = " ";
            }

            if(sizeof($item['color']) > 0){
                $sizes = CatColorsItem::whereIn('id',$item['color'])->get();
                $item['color'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['color'] = " ";
            }

            if(sizeof($item['status']) > 0){
                $sizes = CatItemStatus::whereIn('id',$item['status'])->get();
                $item['status'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['status'] = " ";
            }

            $price_formated = $item['sale_unit_price'];
            $price_formated = $item['currency_type_symbol'].number_format($item['sale_unit_price'], 2);
            $item['sale_unit_price'] = $price_formated;

            return $item;
        });

        $dataItems = $itemCollect->flatMap(function ($item) use ($columnsKey)  {
            return array_map(function () use ($item,$columnsKey) {
                $item = array_intersect_key($item, array_flip($columnsKey));
                $orderedItem = array_replace(array_flip($columnsKey), $item);
                return $orderedItem ;
            }, range(1, $item['quantity_printer']));
        });

        $nombre_archivo = "TxtBartender".Carbon::now();

        $response = new StreamedResponse(function () use ($dataItems,$columnSelected) {
            $handle = fopen('php://output', 'w');

            $headers = array_values($columnSelected);

            fwrite($handle, implode(',', $headers) . "\n");

            foreach ($dataItems as $item) {
                $data = array_values($item);
                fwrite($handle, implode(',', $data) . "\n");
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$nombre_archivo.'"');

        return $response;

    }

    private function getColumnsToBartender($columns){

        $optionalColumns = [
            'internal_id' => 'Código Interno',
            'description' => 'Nombre',
            'barcode' => 'Código de barras',
            'category' => 'Categoría',
            'unit_type_id' => 'Unidad',
            'brand' => 'Marca',
            'sale_unit_price' => 'Precio',
            'size' => 'Talla',
            'color' => 'Colores',
            'status' => 'Status'
        ];

        $selected = array_intersect_key($optionalColumns, array_flip($columns));

        return $selected;
    }
 


}
