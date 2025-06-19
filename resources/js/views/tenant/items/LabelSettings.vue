<template>
    <el-dialog :close-on-click-modal="false" 
        :title="titleDialog" 
        :visible="showDialogLabel"
        append-to-body class="pt-0"
        top="7vh" 
        width="65%" 
        @close="close" 
        @open="create">
        <form autocomplete="off" @submit.prevent="submit">
            <el-tabs v-model="activeName">
                <el-tab-pane class name="first">
                   
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Nombre Producto</label>
                                <el-input v-model="form.description" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Modelo</label>
                                <el-input v-model="form.model" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Código Interno</label>
                                <el-input v-model="form.internal_id" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Código de Barras</label>
                                <el-input v-model="form.barcode" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Altura de las barras</label>
                                <el-input v-model="form.barcode_height"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ancho de las barras</label>
                                <el-input v-model="form.barcode_width" :min="1" :max="100"/>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Número de columnas</label>
                                <el-input v-model="form.columns" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tamaño del pdf</label>
                                <el-input v-model="form.tamañopdf" />
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group"><br>
                                <el-checkbox v-model="form.has_Name"
                                   >Nombre
                                </el-checkbox>
                                  <el-checkbox v-model="form.has_Model"
                                   >Modelo
                                </el-checkbox>
                                  <el-checkbox v-model="form.has_Codin"
                                   >Cod. Interno
                                </el-checkbox>      
                                <!--<el-button type="primary"@click.prevent="
                                    $emit('print-barcode', form)"
                                    >Imprimir</el-button>-->
                                  
                                   <!--<el-button type="primary" @click.prevent="
                                $emit('print-barcodex',form,form.columns)">
                                imprimir</el-button>
                           native-type="submit" evento para guardar no importa donde lo pongas guarda igual    -->
                            </div>
                        </div>
                    </div>
                </el-tab-pane>
            </el-tabs>
            <div class="text-right mt-3">
                <el-button type="primary" @click.prevent="handleImprimir">Imprimir</el-button>
                <el-button @click.prevent="close()">Cancelar</el-button>
            </div>
        </form>
        <div class="iframe-container">
        <iframe 
            v-if="isFormReady && pdfUrl"
            :src="pdfUrl"
            width="90%"
            height="300px"
            style="border: none;"
            >
        </iframe>
        </div>
    </el-dialog>
</template>
<style scoped>
.iframe-container {
  display: flex;
  justify-content: center;
  margin-top: 20px; /* opcional, para separarlo del formulario */
}
</style>
<script>
import LotsForm from './partials/lots.vue'
import ExtraInfo from './partials/extra_info'
import {mapActions, mapState} from "vuex";
import {ItemOptionDescription, ItemSlotTooltip} from "../../../helpers/modal_item";
import axios from 'axios';

export default {
    name: "LabelSettings",
    props: [
        'showDialogLabel',
        'recordId',
        'external',
        'type',
        'pharmacy',
        'onlyShowAllDetails',
    ],
    components: {
        LotsForm,
        ExtraInfo
    },
    computed: {
         isFormReady() {
    return (
      this.form.id &&
      this.form.columns &&
      this.form.barcode_height !== null &&
      this.form.barcode_width !== null &&
      this.form.tamañopdf !== null &&
      this.form.has_Name !== null &&
      this.form.has_Model !== null &&
      this.form.has_Codin !== null
    );
    },
        forOnlyShowAllDetails()
        {
            if(this.onlyShowAllDetails != undefined && this.onlyShowAllDetails != null) return this.onlyShowAllDetails

            return false
        },
        ...mapState([
            'colors',
            'CatItemSize',
            'CatItemUnitsPerPackage',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemMoldCavity',
            'CatItemProductFamily',
            'config',
        ]),
        isService: function () {
            // Tener en cuenta que solo oculta las pestañas para tipo servicio.
            if (this.form !== undefined) {
                // Es servicio por selección
                if (this.form.unit_type_id !== undefined && this.form.unit_type_id === 'ZZ') {
                    if (
                        this.activeName == 'second' ||
                        this.activeName == 'third' ||
                        this.activeName == 'five'
                    ) {
                        this.activeName = 'first';
                    }
                    return true;
                }
            }
            return false;
        },
        canSeeProduction:function(){
            if(this.config && this.config.production_app) return this.config.production_app
            return false;
        },
        requireSupply:function(){

            if(this.form.is_for_production) {

                if( this.form.is_for_production == true) return true
            };
            return false;
        },

        canShowExtraData: function () {
            if (this.config && this.config.show_extra_info_to_item !== undefined) {
                return this.config.show_extra_info_to_item;
            }
            return false;
        },
        showPharmaElement() {

            if (this.fromPharmacy === true) return true;
            if (this.config.is_pharmacy === true) return true;
            return false;
        },
        showPointSystem()
        {
            if(this.config) return this.config.enabled_point_system

            return false
        },
        showRestrictSaleItemsCpe()
        {
            if(this.config) return this.config.restrict_sale_items_cpe

            return false
        },
    },

    data() {
        return {
            loading_search: false,
            showDialogLots: false,
            form_category: {add: false, name: null, id: null},
            form_brand: {add: false, name: null, id: null},
            warehouses: [],
            items: [],
            loading_submit: false,
            showPercentagePerception: false,
            has_percentage_perception: false,
            percentage_perception: null,
            enabled_percentage_of_profit: false,
            titleDialog: null,
            resource: 'items',
            errors: {},
            item_suplly: {},
            headers: headers_token,
            form: {
                item_supplies:[],
                is_for_production:false,    
            },
             isInitialLoading: true,
            // configuration: {},
            unit_types: [],
            currency_types: [],
            system_isc_types: [],
            affectation_igv_types: [],
            categories: [],
            brands: [],
            accounts: [],
            show_has_igv: true,
            purchase_show_has_igv: true,
            have_account: false,
            item_unit_type: {
                id: null,
                unit_type_id: null,
                quantity_unit: 0,
                price1: 0,
                price2: 0,
                price3: 0,
                price_default: 2,

            },
            attribute_types: [],
            activeName: 'first',
            fromPharmacy: false,
            inventory_configuration: null,
            pdfUrl: null,
         
        }
    },
    async created() {
        this.loadConfiguration()
        if (this.pharmacy !== undefined && this.pharmacy == true) {
            this.fromPharmacy = true;
        }
        await this.initForm();

        await this.$http.get(`/${this.resource}/tables`)
            .then(response => {
                let data = response.data;
                this.unit_types = data.unit_types
                this.accounts = data.accounts
                this.currency_types = data.currency_types
                this.system_isc_types = data.system_isc_types
                this.affectation_igv_types = data.affectation_igv_types
                this.warehouses = data.warehouses
                this.categories = data.categories
                this.brands = data.brands
                this.attribute_types = data.attribute_types
                // this.config = data.configuration
                if (this.canShowExtraData) {
                    this.$store.commit('setColors', data.colors);
                    this.$store.commit('setCatItemSize', data.CatItemSize);
                    this.$store.commit('setCatItemUnitsPerPackage', data.CatItemUnitsPerPackage);
                    this.$store.commit('setCatItemStatus', data.CatItemStatus);
                    this.$store.commit('setCatItemMoldCavity', data.CatItemMoldCavity);
                    this.$store.commit('setCatItemMoldProperty', data.CatItemMoldProperty);
                    this.$store.commit('setCatItemUnitBusiness', data.CatItemUnitBusiness);
                    this.$store.commit('setCatItemPackageMeasurement', data.CatItemPackageMeasurement);
                    this.$store.commit('setCatItemProductFamily', data.CatItemPackageMeasurement);
                }
                this.$store.commit('setConfiguration', data.configuration);


                this.loadConfiguration()
                this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
                this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
                this.inventory_configuration = data.inventory_configuration;
            })

        this.$eventHub.$on('submitPercentagePerception', (data) => {
            this.form.percentage_perception = data
            if (!this.form.percentage_perception) this.has_percentage_perception = false
        })

        this.$eventHub.$on('reloadTables', () => {
            this.reloadTables()
        })

        await this.setDefaultConfiguration()

        this.$eventHub.$on('establishmentChanged', () => {
            this.loadCurrentEstablishment();
        });

    },
    beforeDestroy() {
        this.$eventHub.$off('establishmentChanged');
    },

    watch: {
        'form.columns'(newVal, oldVal) {
            if (newVal && newVal !== oldVal) {
                this.loadBarcodeConfig(newVal);
            }
        },
        'form.barcode_height': 'loadBarcodePreview',
        'form.barcode_width': 'loadBarcodePreview',
        'form.tamañopdf': 'loadBarcodePreview',
        'form.has_Name': 'loadBarcodePreview',
        'form.has_Model': 'loadBarcodePreview',
        'form.has_Codin': 'loadBarcodePreview',
        
    },
 
    methods: {
        loadBarcodeConfig(columns) {
        axios.get(`/api/barcode-config/${columns}`).then(response => {
            if (response.data.success) {
                const config = response.data.config;
            this.form = {
            ...this.form,
            barcode_height: config.barcode_height,
            barcode_width: config.barcode_width,
            tamañopdf: config.pdf_size,
            columns: config.columns,
            has_Name: config.has_Name !== undefined ? config.has_Name : true,
            has_Model: config.has_Model !== undefined ? config.has_Model : true,
            has_Codin: config.has_Codin !== undefined ? config.has_Codin : true,
            };
            }
            }).catch(() => {
                this.$message.error('Error al cargar configuración');
            });
        },

      
        loadBarcodePreview(){
        const query = new URLSearchParams({
        id: this.form.id,
        format: this.form.columns,
        barcode_height: this.form.barcode_height,
        barcode_width: this.form.barcode_width,
        pdf_size: this.form.tamañopdf,
        has_Name: this.form.has_Name,
        has_Model: this.form.has_Model,
        has_Codin: this.form.has_Codin
        }).toString();
        this.pdfUrl = `/items/export/barcode/print_x?${query}`;
        },
        
    saveBarcodeConfig() {
        axios.post(`/api/barcode-config/${this.form.columns}`, {
        barcode_height: this.form.barcode_height,
        barcode_width: this.form.barcode_width,
        tamañopdf: this.form.tamañopdf,
        columns: this.form.columns,

        has_Name: this.form.has_Name,
        has_Model: this.form.has_Model,
        has_Codin: this.form.has_Codin,
    });
  },

  handleImprimir() {
    this.saveBarcodeConfig(); // Guardar al imprimir
    this.$emit('print-barcodex', this.form, this.form.columns);
  },
        ...mapActions([
            'loadConfiguration',
        ]),
        setDefaultConfiguration() {
            this.form.sale_affectation_igv_type_id = (this.config) ? this.config.affectation_igv_type_id : '10'

            this.$http.get(`/configurations/record`).then(response => {
                this.form.has_igv = response.data.data.include_igv
                this.form.purchase_has_igv = response.data.data.include_igv
                // this.$setStorage('configuration',response.data.data)
                this.$store.commit('setConfiguration', response.data.data);
                this.loadConfiguration()
            })
        },
        purchaseChangeIsc() {

            if (!this.form.purchase_has_isc) {
                this.form.purchase_system_isc_type_id = null
                this.form.purchase_percentage_isc = 0
            }

        },
        changeIsc() {

            if (!this.form.has_isc) {
                this.form.system_isc_type_id = null
                this.form.percentage_isc = 0
            }

        },
        clickAddAttribute() {
            this.form.attributes.push({
                attribute_type_id: null,
                description: null,
                value: null,
                start_date: null,
                end_date: null,
                duration: null,
            })
        },
        async reloadTables() {
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.unit_types = response.data.unit_types
                    this.accounts = response.data.accounts
                    this.currency_types = response.data.currency_types
                    this.system_isc_types = response.data.system_isc_types
                    this.affectation_igv_types = response.data.affectation_igv_types
                    this.warehouses = response.data.warehouses
                    this.categories = response.data.categories
                    this.brands = response.data.brands

                    this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
                    this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
                })
        },
        changeLotsEnabled() {

            // if(!this.form.lots_enabled){
            //     this.form.lot_code = null
            //     this.form.lots = []
            // }

        },
        changeProductioTab(){

        },
        addRowLot(lots) {
            this.form.lots = lots
        },
        clickLotcode() {
            this.showDialogLots = true
        },
        changeHaveAccount() {
            if (!this.have_account) this.form.account_id = null
        },
        changeEnabledPercentageOfProfit() {
            // if(!this.enabled_percentage_of_profit) this.form.percentage_of_profit = 0
        },
        clickDelete(id) {

            this.$http.delete(`/${this.resource}/item-unit-type/${id}`)
                .then(res => {
                    if (res.data.success) {
                        this.loadRecord()
                        this.$message.success('Se eliminó correctamente el registro')
                    }
                })
                .catch(error => {
                    if (error.response.status === 500) {
                        this.$message.error('Error al intentar eliminar');
                    } else {
                        console.log(error.response.data.message)
                    }
                })

        },
        changeHasPerception() {
            if (!this.form.has_perception) {
                this.form.percentage_perception = null
            }
        },
        clickAddRow() {
            this.form.item_unit_types.push({
                id: null,
                description: null,
                unit_type_id: 'NIU',
                quantity_unit: 0,
                price1: 0,
                price2: 0,
                price3: 0,
                price_default: 2,
                barcode: null
            })
        },
        clickCancel(index) {
            this.form.item_unit_types.splice(index, 1)
        },
        initForm() {
            this.loading_submit = false,
            this.errors = {}
            this.form = {
                has_Codin:false,
                has_Model:false,
                has_Name:false,
                id: null,
                colors: [],
                item_type_id: '01',
                internal_id: null,
                item_code: null,
                item_code_gs1: null,
                description: null,
                name: null,
                second_name: null,
                unit_type_id: 'NIU',
                currency_type_id: 'PEN',
                sale_unit_price: 0,
                purchase_unit_price: 0,
                has_isc: false,
                system_isc_type_id: null,
                percentage_isc: 0,
                suggested_price: 0,
                sale_affectation_igv_type_id: null,
                purchase_affectation_igv_type_id: null,
                calculate_quantity: false,
                stock: 0,
                stock_min: 1,
                has_igv: true,
                has_perception: false,
                item_unit_types: [],
                percentage_of_profit: 0,
                percentage_perception: null,
                image: null,
                image_url: null,
                temp_path: null,
                is_set: false,
                account_id: null,
                category_id: null,
                brand_id: null,
                date_of_due: null,
                lot_code: null,
                line: null,
                lots_enabled: false,
                lots: [],
                attributes: [],
                series_enabled: false,
                purchase_has_igv: true,
                web_platform_id: null,
                has_plastic_bag_taxes: false,
                item_warehouse_prices: [],
                item_supplies:[],

                purchase_has_isc: false,
                purchase_system_isc_type_id: null,
                purchase_percentage_isc: 0,
                subject_to_detraction: false,

                exchange_points: false,
                quantity_of_points: 0,
                factory_code: null,
                restrict_sale_cpe: false,
                warehouse_id: null,
            }
         
            this.show_has_igv = true
            this.purchase_show_has_igv = true
            this.enabled_percentage_of_profit = false
            this.loadCurrentEstablishment()
        },
        onSuccess(response, file, fileList) {
            if (response.success) {
                this.form.image = response.data.filename
                this.form.image_url = response.data.temp_image
                this.form.temp_path = response.data.temp_path
            } else {
                this.$message.error(response.message)
            }
        },
        changeAffectationIgvType() {

            let affectation_igv_type_exonerated = [20, 21, 30, 31, 32, 33, 34, 35, 36, 37]
            let is_exonerated = affectation_igv_type_exonerated.includes((parseInt(this.form.sale_affectation_igv_type_id)));

            if (is_exonerated) {
                this.show_has_igv = false
                this.form.has_igv = true
            } else {
                this.show_has_igv = true
            }

        },
        changePurchaseAffectationIgvType() {

            let affectation_igv_type_exonerated = [20, 21, 30, 31, 32, 33, 34, 35, 36, 37]
            let is_exonerated = affectation_igv_type_exonerated.includes((parseInt(this.form.purchase_affectation_igv_type_id)));

            if (is_exonerated) {
                this.purchase_show_has_igv = false
                this.form.purchase_has_igv = true
            } else {
                this.purchase_show_has_igv = true
            }

        },
        resetForm() {

            this.initForm()
            this.form.sale_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
            this.form.purchase_affectation_igv_type_id = (this.affectation_igv_types.length > 0) ? this.affectation_igv_types[0].id : null
            this.setDefaultConfiguration()
        },
        setDialogTitle()
        {
            if(this.forOnlyShowAllDetails)
            {
                this.titleDialog = 'Ver Producto'
            }
            else
            {
                this.titleDialog = (this.recordId) ? 'Editar Producto' : 'Nuevo Producto'
            }
        },

        async create() {
            // console.log(this.warehouses)
            // this.warehouses = this.warehouses.map(w => {
            //     delete w.price;
            //     return w;
            // });
this.activeName =  'first'
            if (this.type) {
                if (this.type !== 'PRODUCTS') {
                    this.form.unit_type_id = 'ZZ';
                }
            }

            this.setDialogTitle()

            if (this.recordId) {
                await this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data  
                        /*this.form = {
                        ...this.form,
                        ...response.data.data                    
                        };*/
                        this.has_percentage_perception = (this.form.percentage_perception) ? true : false
                        this.changeAffectationIgvType()
                        this.changePurchaseAffectationIgvType()
                        // let warehousePrices = response.data.data.warehouse_prices;
                        // console.error(warehousePrices);
                        // if (warehousePrices.length > 0) {
                        //     this.warehouses = this.warehouses.map(w => {
                        //         let price = warehousePrices.find(wp => wp.warehouse_id === w.id);
                        //         if (price) {
                        //             var priceToJson = {...price};
                        //             w.price = priceToJson.price;
                        //         }
                        //         return w;
                        //     });
                        // } else {
                        //     this.warehouses = this.warehouses.map(w => {
                        //         delete w.price;
                        //         return w;
                        //     });
                        // }
                    })

            }

            this.setDataToItemWarehousePrices()

            if (this.warehouses.length === 0) {
                await this.reloadTables();
            }
    
            this.setDialogTitle();

            if (this.recordId) {
                await this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data;
                        this.has_percentage_perception = (this.form.percentage_perception) ? true : false;
                        this.changeAffectationIgvType();
                        this.changePurchaseAffectationIgvType();
                    });
            } else {
                
                this.loadCurrentEstablishment();
            }

    this.setDataToItemWarehousePrices();

    
    const cols = this.form.columns || 1;
    try {
        const response = await axios.get(`/api/barcode-config/${cols}`);

    if (response.data.success) {
        const config = response.data.config;

        this.form = {
            ...this.form,
            barcode_height: config.barcode_height,
            barcode_width: config.barcode_width,
            tamañopdf: config.pdf_size,
            columns: config.columns,
            has_Name: config.has_Name !== undefined ? config.has_Name : false,
            has_Model: config.has_Model !== undefined ? config.has_Model : false,
            has_Codin: config.has_Codin !== undefined ? config.has_Codin : false,
        };
    } else {
        this.$message.warning('No hay configuración guardada para ese formato');
    }
    // Solo construir la URL cuando ya cargó bien todo lo anterior
    this.loadBarcodePreview(cols);
    } catch (error) {
        console.error('Error al cargar configuración del código de barras:', error);
        this.$message.error('Error al cargar configuración');
    }
    },
        setDataToItemWarehousePrices() {

            this.warehouses.forEach(warehouse => {

                let item_warehouse_price = _.find(this.form.item_warehouse_prices, {warehouse_id: warehouse.id})

                if (!item_warehouse_price) {

                    this.form.item_warehouse_prices.push({
                        id: null,
                        item_id: null,
                        warehouse_id: warehouse.id,
                        price: null,
                        description: warehouse.description,
                    })
                }

            });

            this.form.item_warehouse_prices = _.orderBy(this.form.item_warehouse_prices, ['warehouse_id'])

        },
        loadRecord() {
            if (this.recordId) {
                this.$http.get(`/${this.resource}/record/${this.recordId}`)
                    .then(response => {
                        this.form = response.data.data
                        console.error(this.form.is_for_production)
                        this.changeAffectationIgvType()
                        this.changePurchaseAffectationIgvType()
                    })
            }
        },
        calculatePercentageOfProfitBySale() {
            let difference = parseFloat(this.form.sale_unit_price) - parseFloat(this.form.purchase_unit_price);

            if (parseFloat(this.form.purchase_unit_price) === 0) {
                this.form.percentage_of_profit = 0;
            } else {
                if (this.enabled_percentage_of_profit) this.form.percentage_of_profit = difference / parseFloat(this.form.purchase_unit_price) * 100;
            }
        },
        calculatePercentageOfProfitByPurchase() {
            if (this.form.percentage_of_profit === '') {
                this.form.percentage_of_profit = 0;
            }

            if (this.enabled_percentage_of_profit) this.form.sale_unit_price = (this.form.purchase_unit_price * (100 + parseFloat(this.form.percentage_of_profit))) / 100
        },
        calculatePercentageOfProfitByPercentage() {
            if (this.form.percentage_of_profit === '') {
                this.form.percentage_of_profit = 0;
            }

            if (this.enabled_percentage_of_profit) this.form.sale_unit_price = (this.form.purchase_unit_price * (100 + parseFloat(this.form.percentage_of_profit))) / 100
        },
        validateItemUnitTypes() {

            let error_by_item = 0

            if (this.form.item_unit_types.length > 0) {

                this.form.item_unit_types.forEach(item => {

                    if (parseFloat(item.quantity_unit) < 0.0001) {
                        error_by_item++
                    }

                })

            }

            return error_by_item

        },
        async submit() {

            const stock = parseInt(this.form.stock);
            if(isNaN(stock)){
                 return this.$message.error('Stock Inicial debe ser un número entero.');
            }

            if (this.validateItemUnitTypes() > 0) return this.$message.error('El campo factor no puede ser menor a 0.0001');

            if (this.fromPharmacy === true) {
                if (this.form.cod_digemid === null) {
                    return this.$message.error('Debe haber un codigo DIGEMID');
                }
                if (this.form.sanitary === null) {
                    return this.$message.error('Debe haber un Registro Sanitario');
                }
            }
            if (this.form.has_perception && !this.form.percentage_perception) return this.$message.error('Ingrese un porcentaje');

            if (this.form.lots_enabled && stock > 0) {

                if (!this.form.lot_code)
                    return this.$message.error('Código de lote es requerido');

                if (!this.form.date_of_due)
                    return this.$message.error('Fecha de vencimiento es requerido si lotes esta habilitado.');
            }

            if (!this.recordId && this.form.series_enabled) {

                if (this.form.lots.length > this.form.stock)
                    return this.$message.error('La cantidad de series registradas es superior al stock');

                if (this.form.lots.length != this.form.stock)
                    return this.$message.error('La cantidad de series registradas son diferentes al stock');
            }

            if (this.form.has_isc) {
                if (this.form.percentage_isc <= 0)
                    return this.$message.error('El porcentaje isc debe ser mayor a 0');
            }

            if (this.form.purchase_has_isc) {
                if (this.form.purchase_percentage_isc <= 0)
                    return this.$message.error('El porcentaje isc debe ser mayor a 0 (Compras)');
            }

            this.loading_submit = true


            await this.$http.post(`/${this.resource}`, this.form)
                .then(response => {
                    console.log(response.data)
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        if (this.external) {
                            this.$eventHub.$emit('reloadDataItems', response.data.id)
                        } else {
                            this.$eventHub.$emit('reloadData')
                        }
                        this.close()
                    } else {
                        this.$message.error(response.data.message)
                    }
                })
                .catch(error => {
                    if (error.response.status === 422) {
                        this.errors = error.response.data
                    } else {
                        console.log(error)
                        this.$message.error(error.response.data.message)
                    }
                })
                .then(() => {
                    this.loading_submit = false
                })
        },
        close() {
            this.$emit('update:showDialogLabel', false)
            this.resetForm()
        },
        changeHasIsc() {
            this.form.system_isc_type_id = null
            this.form.percentage_isc = 0
            this.form.suggested_price = 0
        },
        changeSystemIscType() {
            if (this.form.system_isc_type_id !== '03') {
                this.form.suggested_price = 0
            }
        },
        saveCategory() {
            this.form_category.add = false

            this.$http.post(`/categories`, this.form_category)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.categories.push(response.data.data)
                        this.form_category.name = null
                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })
        },
        saveBrand() {
            this.form_brand.add = false

            this.$http.post(`/brands`, this.form_brand)
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(response.data.message)
                        this.brands.push(response.data.data)
                        this.form_brand.name = null

                    } else {
                        this.$message.error('No se guardaron los cambios')
                    }
                })
                .catch(error => {

                })


        },
        changeAttributeType(index) {
            let attribute_type_id = this.form.attributes[index].attribute_type_id
            let attribute_type = _.find(this.attribute_types, {id: attribute_type_id})
            this.form.attributes[index].description = attribute_type.description
        },
        clickRemoveAttribute(index) {
            this.form.attributes.splice(index, 1)
        },
        async searchRemoteItems(input) {
            if (input.length > 2) {
                this.loading_search = true
                const params = {
                    'input': input,
                    'search_by_barcode': this.search_item_by_barcode ? 1 : 0,
                    'production':1
                }
                await this.$http.get(`/${this.resource}/search-items/`, {params})
                    .then(response => {
                        this.items = response.data.items
                        this.loading_search = false
                        // this.enabledSearchItemsBarcode()
                        // this.enabledSearchItemBySeries()
                        if (this.items.length == 0) {
                            // this.filterItems()
                        }
                    })
            } else {
                // await this.filterItems()
            }

        },
        getItems() {
            this.$http.get(`/${this.resource}/item/tables`).then(response => {
                this.items = response.data.items
            })
        },
        changeItem() {
            this.getItems();
            this.item_suplly = _.find(this.items, {'id': this.item_suplly});
            /*
            this.form.unit_price = this.item_suplly.sale_unit_price;

            this.lots = this.item_suplly.lots

            this.form.has_igv = this.item_suplly.has_igv;

            this.form.affectation_igv_type_id = this.item_suplly.sale_affectation_igv_type_id;
            this.form.quantity = 1;
            this.item_unit_types = this.item_suplly.item_unit_types;

            (this.item_unit_types.length > 0) ? this.has_list_prices = true : this.has_list_prices = false;
            */

        },
        focusSelectItem() {
            this.$refs.selectSearchNormal.$el.getElementsByTagName('input')[0].focus()
        },

        ItemSlotTooltipView(item) {
            return ItemSlotTooltip(item);
        },
        ItemOptionDescriptionView(item) {
            return ItemOptionDescription(item)
        },
        clickAddSupply(){
            // item_supplies
            if(this.form.supplies === undefined) this.form.supplies = [];
            let item = this.item_suplly;
            if(item === null) return false;
            if(item === undefined) return false;
            if(item.id=== undefined) return false;
            this.items = [];
            this.item_suplly = {}

            item.item_id = this.form.id
            //item.individual_item_id = item.id
            item.individual_item_id = item.id
            item.individual_item = {
                'description':item.description
            }
            //item.individual_item = item
            // item.quantity = 0
            //if(isNaN(item.quantity)) item.quantity = 0 ;
            this.form.supplies.push(item)
            this.changeItem()


        },
        loadCurrentEstablishment() {
            this.$http.get('/establishments/getEstablishmentActive')
                .then(response => {
                    if (response.data.success) {
                        const establishment = response.data.establishment;
                        
                        if (establishment && this.warehouses.length > 0) {
                            const relatedWarehouse = this.warehouses.find(w => 
                                w.description.includes(establishment.description));
                    
                            if (relatedWarehouse) {
                                this.form.warehouse_id = relatedWarehouse.id;
                            } else {
                                
                                this.form.warehouse_id = this.warehouses[0].id;
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error al obtener la sucursal activa:', error);
                });
        },
    }
}
</script>
