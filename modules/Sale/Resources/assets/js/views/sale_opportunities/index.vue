<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/sale-opportunities"><svg  xmlns="http://www.w3.org/2000/svg" style="margin-top: -5px;" width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-edit"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1" /><path d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z" /><path d="M16 5l3 3" /></svg></a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Oportunidad de venta</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a :href="`/${resource}/create`" class="btn btn-custom btn-sm  mt-2 mr-2"><i class="fa fa-plus-circle"></i> Nuevo</a>
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <div class="data-table-visible-columns">
                <el-dropdown :hide-on-click="false">
                    <el-button type="primary">
                        Mostrar/Ocultar columnas<i class="el-icon-arrow-down el-icon--right"></i>
                    </el-button>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item v-for="(column, index) in columns" :key="index">
                            <el-checkbox v-model="column.visible" @change="saveColumnVisibility">{{ column.title }}</el-checkbox>
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-left">Fecha Emisión</th>
                        <th v-if="columns.sale.visible">Vendedor</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>O. Venta</th>
                        <th v-if="columns.quotation.visible">Cotización</th>
                        <th >O. Compra</th>
                        <th class="text-center">Moneda</th>
                        <th class="text-center">Archivos</th>
                        <th class="text-right" v-if="columns.total_exportation.visible">T.Exportación</th>
                        <th class="text-right" v-if="columns.total_unaffected.visible">T.Inafecta</th>
                        <th class="text-right" v-if="columns.total_exonerated.visible">T.Exonerado</th>
                        <th class="text-right" v-if="columns.total_taxed.visible">T.Gravado</th>
                        <th class="text-right" v-if="columns.total_igv.visible">T.Igv</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">Descarga</th>
                        <th class="text-right">Acciones</th>
                    <tr>
                    <tr slot-scope="{ index, row }" :class="{ anulate_color : row.state_type_id == '11' }">
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-left">{{ row.date_of_issue }}</td>
                        <td v-if="columns.sale.visible">{{ row.user_name }}</td>
                        <td>{{ row.customer_name }}<br/><small v-text="row.customer_number"></small></td>
                        <td>{{row.state_type_description}}</td>
                        <td>{{ row.number_full }}</td>
                        <td v-if="columns.quotation.visible">{{ row.quotation_number_full }}</td>
                        <td >{{ row.purchase_order_number_full }}</td>
                        <td class="text-center">{{ row.currency_type_id }}</td>
                        <td class="text-center">


                            <el-popover
                                placement="right"
                                width="400"
                                trigger="click">

                                <div class="col-md-12">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nombre</th>
                                                    <th>Descarga</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr v-for="(row, index) in row.files" :key="index">
                                                    <td>{{index+1}}</td>
                                                    <td>{{row.filename}}</td>
                                                    <td class="text-center">
                                                        <button  type="button" class="btn waves-effect waves-light btn-xs btn-primary" @click.prevent="clickDownloadFile(row.filename)">
                                                            <i class="fas fa-file-download"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <el-button class="second-buton" slot="reference"> <i class="fa fa-eye"></i></el-button>
                            </el-popover>

                        </td>
                        <td class="text-right"  v-if="columns.total_exportation.visible" >{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total_exportation }}</td>
                        <td class="text-right" v-if="columns.total_unaffected.visible">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total_unaffected }}</td>
                        <td class="text-right" v-if="columns.total_exonerated.visible">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total_exonerated }}</td>
                        <td class="text-right" v-if="columns.total_taxed.visible">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}}{{ row.total_taxed }}</td>
                        <td class="text-right" v-if="columns.total_igv.visible">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total_igv }}</td>
                        <td class="text-right">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total }}</td>
                        <td class="text-right">

                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickDownload(row.external_id)">PDF</button>
                        </td>

                        <td class="text-right">
                            <a v-if="row.btn_generate_oc && canGenerarte"   :href="`/purchase-orders/sale-opportunity/${row.id}`" type="button" class="btn waves-effect waves-light btn-xs btn-warning">
                                Generar O. Compra
                            </a>
                            <a v-if="row.btn_generate && canGenerarte"   :href="`/quotations/create/${row.id}`" type="button" class="btn waves-effect waves-light btn-xs btn-primary">
                                Generar cotizacion
                            </a>

                            <a v-if="row.state_type_id != '11' && (row.btn_generate && row.btn_generate_oc)" :href="`/${resource}/create/${row.id}`" type="button" class="btn waves-effect waves-light btn-xs btn-info">Editar</a>

                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                                    @click.prevent="clickOptions(row.id)">Opciones</button>
                        </td>

                    </tr>
                </data-table>
            </div>


            <sale-opportunities-options :showDialog.sync="showDialogOptions"
                              :recordId="recordId"
                              :showGenerate="true"
                              :showClose="true"></sale-opportunities-options>

        </div>
    </div>
</template>
<style scoped>
    .anulate_color{
        color:red;
    }
</style>
<script>

    import SaleOpportunitiesOptions from './partials/options.vue'
    import DataTable from '@components/DataTable.vue'
    import {deletable} from '@mixins/deletable'

    export default {
        props:['typeUser','canGenerate'],
        mixins: [deletable],
        components: {DataTable,SaleOpportunitiesOptions},
        computed:{
          canGenerarte: function(){
               if(this.typeUser == 'admin' || this.canGenerate == true){
                   return true;
               }
               return false;
          }
        },
        data() {
            return {
                resource: 'sale-opportunities',
                recordId: null,
                showDialogOptions: false,
                columns: {
                    total_exportation: {
                        title: 'T.Exportación',
                        visible: false
                    },
                    total_unaffected: {
                        title: 'T.Inafecto',
                        visible: false
                    },
                    total_exonerated: {
                        title: 'T.Exonerado',
                        visible: false
                    },
                    total_taxed: {
                        title: 'T.Gravado',
                        visible: false
                    },
                    total_igv: {
                        title: 'T.IGV',
                        visible: false
                    },
                    quotation: {
                        title: 'Cotización',
                        visible: true
                    },
                    sale: {
                        title: 'Vendedor',
                        visible: false
                    },
                }
            }
        },
        created() {
            this.loadColumnVisibility();
        },
        methods: {
            saveColumnVisibility() {
                localStorage.setItem('columnVisibility', JSON.stringify(this.columns));
            },
            loadColumnVisibility() {
                const savedColumns = localStorage.getItem('columnVisibility');
                if (savedColumns) {
                    this.columns = JSON.parse(savedColumns);
                }
            },
            clickDownloadFile(filename) {
                window.open(
                    `/${this.resource}/download-file/${filename}`,
                    "_blank"
                );
            },
            clickDownload(external_id) {
                window.open(`/${this.resource}/download/${external_id}`, '_blank');
            },
            clickEdit(id)
            {
                this.recordId = id
                this.showDialogFormEdit = true
            },
            clickOptions(recordId = null) {
                this.recordId = recordId
                this.showDialogOptions = true
            },
            clickOptionsPdf(recordId = null) {
                this.recordId = recordId
                this.showDialogOptionsPdf = true
            },
        }
    }
</script>
