<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/packaging">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        style="margin-top: -5px;"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="icon icon-tabler icons-tabler-outline icon-tabler-building-factory-2"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path d="M3 21h18" />
                        <path d="M5 21v-12l5 4v-4l5 4h4" />
                        <path
                            d="M19 21v-8l-1.436 -9.574a.5 .5 0 0 0 -.495 -.426h-1.145a.5 .5 0 0 0 -.494 .418l-1.43 8.582"
                        />
                        <path d="M9 17h1" />
                        <path d="M14 17h1" />
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active">
                    <span>
                        Zona de embalaje
                    </span>
                </li>
            </ol>
            <div class="right-wrapper pull-right">
                <template>
                    <button
                        type="button"
                        class="btn btn-custom btn-sm  mt-2 mr-2"
                        @click.prevent="clickCreate()"
                    >
                        <i class="fa fa-plus-circle"></i>
                        Nuevo
                    </button>
                </template>
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">
                    Zona de embalaje
                </h3>
            </div> -->
            <div class="card-body">
                <div class="row">
                    <div class="col-12">
                        <el-button
                            class="submit"
                            type="danger"
                            icon="el-icon-tickets"
                            @click.prevent="clickDownloadPdf()"
                            >Exportar PDF</el-button
                        >

                        <el-button
                            class="submit"
                            type="success"
                            @click.prevent="clickDownloadExcel()"
                            ><i class="fa fa-file-excel"></i> Exportal
                            Excel</el-button
                        >
                    </div>
                    <div class="col-lg-12">
                    <div class="scroll-shadow shadow-left" v-show="showLeftShadow"></div>
                    <div class="scroll-shadow shadow-right" v-show="showRightShadow"></div>
                    <div class="col-12 p-t-20 table-responsive" ref="scrollContainer">
                        <table class="table">
                            <thead>
                                <tr>
                                    <!-- <th>#</th> -->
                                    <th class="text-right">
                                        Número de registro
                                    </th>
                                    <th class="text-right">Número de ficha</th>
                                    <th>Producto</th>
                                    <th>Usuario</th>
                                    <th>Sucursal</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right"># Paquetes</th>
                                    <th>Lote</th>
                                    <th>Fecha de inicio</th>
                                    <th>Fecha de fin</th>
                                    <th>Colaborador</th>
                                    <th>Comentario</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, index) in records">
                                    <!-- <td>{{ index + 1 }}</td> -->
                                    <td class="text-right">000{{ row.id }}</td>
                                    <td class="text-right">{{ row.name }}</td>
                                    <td>{{ row.item.name }}</td>
                                    <td>{{ row.user }}</td>
                                    <td>{{ row.stablishment }}</td>
                                    <td class="text-right">
                                        {{ row.quantity }}
                                    </td>
                                    <td class="text-right">
                                        {{ row.number_packages }}
                                    </td>
                                    <td>{{ row.lot_code }}</td>
                                    <td>
                                        {{
                                            formatDateTime(
                                                `${row.date_start} ${
                                                    row.time_start
                                                }`
                                            )
                                        }}
                                    </td>
                                    <td>
                                        {{
                                            formatDateTime(
                                                `${row.date_end} ${
                                                    row.time_end
                                                }`
                                            )
                                        }}
                                    </td>
                                    <td>{{ row.packaging_collaborator }}</td>
                                    <td>{{ row.observation }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    </div>                    
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { mapActions, mapState } from "vuex/dist/vuex.mjs";
import { deletable } from "../../../../../../../resources/js/mixins/deletable";

export default {
    props: ["configuration", "typeUser"],
    mixins: [deletable],
    components: {},
    computed: {
        ...mapState(["config"]),
        columnsComputed: function() {
            return this.columns;
        }
    },
    data() {
        return {
            can_add_new_product: false,
            showDialog: false,
            showImportSetDialog: false,
            showImportSetIndividualDialog: false,
            showWarehousesDetail: false,
            resource: "packaging",
            recordId: null,
            warehousesDetail: [],
            // config: {},
            columns: {
                description: {
                    title: "Descripción",
                    visible: true
                },
                item_code: {
                    title: "Cód. SUNAT",
                    visible: false
                },
                /*
                purchase_unit_price: {
                    title: 'P.Unitario (Compra)',
                    visible: false
                },
                purchase_has_igv_description: {
                    title: 'Tiene Igv (Compra)',
                    visible: false
                },*/
                model: {
                    title: "Modelo",
                    visible: false
                }
                /*
                brand: {
                    title: 'Marca',
                    visible: false
                },
                sanitary: {
                    title: 'N° Sanitario',
                    visible: false
                },
                cod_digemid: {
                    title: 'DIGEMID',
                    visible: false
                },

                 */
            },
            pagination: {},
            records: [],
            showLeftShadow: false,
            showRightShadow: false,
        };
    },
    created() {
        this.loadConfiguration();
        this.$store.commit("setConfiguration", this.configuration);
        if (this.config.is_pharmacy !== true) {
            // delete this.columns.sanitary;
            // delete this.columns.cod_digemid;
        }

        return this.$http
            .get(`/${this.resource}/records`)
            .then(response => {
                this.records = response.data.data;
                this.pagination = response.data.meta;
                this.pagination.per_page = parseInt(
                    response.data.meta.per_page
                );
            })
            .catch(error => {})
            .then(() => {
                this.loading_submit = false;
            });

        //this.canCreateProduct();
    },
    mounted() {
        this.$nextTick(() => {
            const el = this.$refs.scrollContainer;
            if (el) {
                el.addEventListener('scroll', this.checkScrollShadows);
                this.checkScrollShadows();
            }
        });
    },
    methods: {
        checkScrollShadows() {
            const el = this.$refs.scrollContainer;
            if (!el) return;
            
            const scrollLeft = el.scrollLeft;
            const scrollRight = el.scrollWidth - el.clientWidth - scrollLeft;
            
            this.showLeftShadow = scrollLeft > 1;
            this.showRightShadow = scrollRight > 1;
        },
        formatDateTime(dateTime) {
            if (!dateTime) return null;
            const parsedDate = moment(dateTime, "YYYY-MM-DD HH:mm:ss");
            return parsedDate.isValid()
                ? parsedDate.format("DD-MM-YYYY h:mmA")
                : null;
        },
        ...mapActions(["loadConfiguration"]),
        canCreateProduct() {
            if (this.typeUser === "admin") {
                this.can_add_new_product = true;
            } else if (this.typeUser === "seller") {
                if (
                    this.config !== undefined &&
                    this.config.seller_can_create_product !== undefined
                ) {
                    this.can_add_new_product = this.config.seller_can_create_product;
                }
            }
            return this.can_add_new_product;
        },
        clickImportSetIndividual() {
            this.showImportSetIndividualDialog = true;
        },
        clickWarehouseDetail(warehouses) {
            this.warehousesDetail = warehouses;
            this.showWarehousesDetail = true;
        },
        clickCreate(recordId = null) {
            window.location.href = `./${this.resource}/create`;

            // this.recordId = recordId
            // this.showDialog = true
        },
        clickImportSet() {
            this.showImportSetDialog = true;
        },
        clickDelete(id) {
            this.destroy(`/${this.resource}/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickDownloadPdf() {
            window.open(`${this.resource}/pdf`, "_blank");
        },
        clickDownloadExcel() {
            window.open(`${this.resource}/excel`, "_blank");
        }
    }
};
</script>
