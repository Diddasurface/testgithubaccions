<template>
    <div class="purchases">
        <div class="page-header pr-0">
            <h2>
                <a href="/purchases">
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
                        class="icon icon-tabler icons-tabler-outline icon-tabler-shopping-bag"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M6.331 8h11.339a2 2 0 0 1 1.977 2.304l-1.255 8.152a3 3 0 0 1 -2.966 2.544h-6.852a3 3 0 0 1 -2.965 -2.544l-1.255 -8.152a2 2 0 0 1 1.977 -2.304z"
                        />
                        <path d="M9 11v-5a3 3 0 0 1 6 0v5" />
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Compras</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a
                    :href="`/${resource}/create`"
                    class="btn btn-custom btn-sm  mt-2 mr-2"
                    ><i class="fa fa-plus-circle"></i> Nuevo</a
                >
                <button
                    @click.prevent="clickImport()"
                    type="button"
                    class="btn btn-custom btn-sm  mt-2 mr-2"
                >
                    <i class="fa fa-upload"></i> Importar
                </button>
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <div class="data-table-visible-columns">
                <el-dropdown :hide-on-click="false">
                    <el-button type="primary">
                        Mostrar/Ocultar columnas<i
                            class="el-icon-arrow-down el-icon--right"
                        ></i>
                    </el-button>
                    <el-dropdown-menu slot="dropdown">
                        <el-dropdown-item
                            v-for="(column, index) in columns"
                            :key="index"
                        >
                            <el-checkbox
                                v-model="column.visible"
                                @change="saveColumnVisibility"
                                >{{ column.title }}</el-checkbox
                            >
                        </el-dropdown-item>
                    </el-dropdown-menu>
                </el-dropdown>
            </div>
            <div class="card-body">
                <data-table :resource="resource">
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-left">F. Emisión</th>
                        <th
                            class="text-center"
                            v-if="columns.date_of_due.visible"
                        >
                            F. Vencimiento
                        </th>
                        <th>Proveedor</th>
                        <th>Estado</th>
                        <th>Estado de pago</th>
                        <th>Número</th>
                        <th>Productos</th>
                        <th>Pagos</th>
                        <!-- <th>F. Pago</th> -->
                        <!-- <th>Estado</th> -->
                        <th class="text-center">Moneda</th>
                        <th class="text-right" v-if="columns.guides.visible">
                            Guía
                        </th>
                        <th
                            class="text-right"
                            v-if="columns.purchase_order.visible"
                        >
                            Orden de compra
                        </th>
                        <!-- <th class="text-right">T.Exportación</th> -->
                        <th
                            v-if="columns.total_free.visible"
                            class="text-right"
                        >
                            T.Gratuita
                        </th>
                        <th
                            v-if="columns.total_unaffected.visible"
                            class="text-right"
                        >
                            T.Inafecta
                        </th>
                        <th
                            v-if="columns.total_exonerated.visible"
                            class="text-right"
                        >
                            T.Exonerado
                        </th>
                        <th
                            v-if="columns.total_taxed.visible"
                            class="text-right"
                        >
                            T.Gravado
                        </th>
                        <th v-if="columns.total_igv.visible" class="text-right">
                            T.Igv
                        </th>
                        <th v-if="columns.total_perception.visible">
                            Percepcion
                        </th>
                        <th class="text-right">Total</th>
                        <!-- <th class="text-center">Descargas</th> -->
                        <th class="text-right">Acciones</th>
                    </tr>
                    <tr slot-scope="{ index, row }">
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-left">
                            {{ formatDate(row.date_of_issue) }}
                        </td>
                        <td
                            v-if="columns.date_of_due.visible"
                            class="text-center"
                            :class="{
                                'text-danger':
                                    row.state_type_payment_description !=
                                        'Pagado' &&
                                    isDateWarning(row.date_of_due)
                            }"
                        >
                            {{ formatDate(row.date_of_due) }}
                        </td>
                        <td>
                            {{ row.supplier_name }}<br /><small
                                v-text="row.supplier_number"
                            ></small>
                        </td>
                        <td>{{ row.state_type_description }}</td>
                        <td
                            :class="
                                row.state_type_payment_description == 'Pagado'
                                    ? 'text-success'
                                    : 'text-warning'
                            "
                        >
                            {{ row.state_type_payment_description }}
                        </td>
                        <td>
                            {{ row.number }}<br />
                            <small
                                v-text="row.document_type_description"
                            ></small
                            ><br />
                        </td>
                        <td>
                            <el-popover
                                placement="right"
                                width="400"
                                trigger="click"
                            >
                                <el-table :data="row.items">
                                    <el-table-column
                                        width="80"
                                        property="key"
                                        label="#"
                                    ></el-table-column>
                                    <!-- <el-table-column width="220" property="description" label="Nombre"></el-table-column> -->

                                    <el-table-column width="220" label="Nombre">
                                        <template slot-scope="scope">
                                            <template
                                                v-if="
                                                    scope.row.name_product_pdf
                                                "
                                            >
                                                <span
                                                    v-html="
                                                        scope.row
                                                            .name_product_pdf
                                                    "
                                                ></span>
                                            </template>
                                            <template v-else>
                                                {{ scope.row.description }}
                                            </template>
                                        </template>
                                    </el-table-column>

                                    <el-table-column
                                        width="90"
                                        property="quantity"
                                        label="Cantidad"
                                    ></el-table-column>
                                </el-table>
                                <el-button
                                    class="second-buton"
                                    slot="reference"
                                >
                                    <i class="fa fa-eye"></i
                                ></el-button>
                            </el-popover>
                        </td>
                        <!-- <td>{{ row.payment_method_type_description }}</td> -->
                        <!-- <td>
                            <template v-for="(it,ind) in row.payments">
                                {{it.payment_method_type_description}} - {{it.payment}}
                            </template>
                        </td> -->
                        <!-- <td>{{ row.state_type_description }}</td> -->
                        <td class="text-right">
                            <button
                                v-if="row.state_type_id != '11'"
                                type="button"
                                style="min-width: 41px"
                                class="btn waves-effect waves-light btn-xs btn-info m-1__2"
                                @click.prevent="clickPurchasePayment(row.id)"
                            >
                                Pagos
                            </button>
                        </td>

                        <td class="text-center">{{ row.currency_type_id }}</td>
                        <td class="text-center" v-if="columns.guides.visible">
                            <span v-for="(item, i) in row.guides" :key="i">
                                {{ item.number }} <br />
                            </span>
                        </td>
                        <!-- <td class="text-right">{{ row.total_exportation }}</td> -->
                        <td
                            v-if="columns.purchase_order.visible"
                            class="text-right"
                        >
                            <span v-if="row.purchase_order"
                                >{{ row.purchase_order.prefix }}-{{
                                    row.purchase_order.id
                                }}</span
                            >
                        </td>
                        <td
                            v-if="columns.total_free.visible"
                            class="text-right"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_free }}
                        </td>
                        <td
                            v-if="columns.total_unaffected.visible"
                            class="text-right"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_unaffected }}
                        </td>
                        <td
                            v-if="columns.total_exonerated.visible"
                            class="text-right"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_exonerated }}
                        </td>
                        <td
                            v-if="columns.total_taxed.visible"
                            class="text-right"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_taxed }}
                        </td>
                        <td v-if="columns.total_igv.visible" class="text-right">
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_igv }}
                        </td>
                        <td
                            v-if="columns.total_perception.visible"
                            class="text-right"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{
                                row.total_perception ? row.total_perception : 0
                            }}
                        </td>
                        <td class="text-right">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total }}</td>
                        <td class="text-right">
                            <template v-if="permissions.edit_purchase">
                                <a
                                    v-if="row.state_type_id != '11'"
                                    :href="`/${resource}/edit/${row.id}`"
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-info"
                                    >Editar</a
                                >
                            </template>
                            <template v-if="permissions.annular_purchase">
                                <button
                                    v-if="row.state_type_id != '11'"
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                    @click.prevent="clickAnulate(row.id)"
                                >
                                    Anular
                                </button>
                            </template>
                            <template
                                v-if="
                                    permissions.delete_purchase &&
                                        row.state_type_id == '11'
                                "
                            >
                                <button
                                    v-if="row.state_type_id == '11'"
                                    type="button"
                                    class="btn waves-effect waves-light btn-xs btn-danger"
                                    @click.prevent="clickDelete(row.id)"
                                >
                                    Eliminar
                                </button>
                            </template>

                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-primary"
                                @click.prevent="clickOptions(row.id)"
                            >
                                Opciones
                            </button>
                            <button
                                type="button"
                                :disabled="disableGuideBtn"
                                class="btn waves-effect waves-light btn-xs btn-warning m-1__2"
                                @click.prevent="clickGuide(row.id)"
                            >
                                Guía
                            </button>
                        </td>

                        <!-- <td class="text-right">
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-danger"
                                    @click.prevent="clickVoided(row.id)"
                                    v-if="row.btn_voided">Anular</button>
                            <a :href="`/${resource}/note/${row.id}`" class="btn waves-effect waves-light btn-xs btn-warning"
                               v-if="row.btn_note">Nota</a>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickResend(row.id)"
                                    v-if="row.btn_resend">Reenviar</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickConsultCdr(row.id)"
                                    v-if="row.btn_consult_cdr">Consultar CDR</button>
                            <button type="button" class="btn waves-effect waves-light btn-xs btn-info"
                                    @click.prevent="clickOptions(row.id)">Opciones</button>
                        </td> -->
                    </tr>
                </data-table>
            </div>

            <!-- <documents-voided :showDialog.sync="showDialogVoided"
                            :recordId="recordId"></documents-voided>

            <document-options :showDialog.sync="showDialogOptions"
                              :recordId="recordId"
                              :showClose="true"></document-options> -->

            <purchase-import
                :showDialog.sync="showImportDialog"
            ></purchase-import>
        </div>

        <tenant-guides-modal
            :show.sync="showModalGuide"
            :id="recordId"
            :type="'purchases'"
        ></tenant-guides-modal>
        <purchase-payments
            :showDialog.sync="showDialogPurchasePayments"
            :purchaseId="recordId"
            :external="true"
        ></purchase-payments>

        <purchase-options
            :showDialog.sync="showDialogOptions"
            :recordId="recordId"
            :showClose="true"
        ></purchase-options>
    </div>
</template>

<script>
import { mapActions, mapState } from "vuex";

// import DocumentsVoided from './partials/voided.vue'
// import DocumentOptions from './partials/options.vue'
import DataTable from "../../../components/DataTable.vue";
import { deletable } from "../../../mixins/deletable";
import PurchaseImport from "./import.vue";
import PurchasePayments from "@viewsModulePurchase/purchase_payments/payments.vue";
import PurchaseOptions from "./partials/options.vue";

export default {
    mixins: [deletable],
    // components: {DocumentsVoided, DocumentOptions, DataTable},
    components: {
        DataTable,
        PurchaseImport,
        PurchasePayments,
        PurchaseOptions
    },
    props: ["typeUser", "configuration"],
    data() {
        return {
            disableGuideBtn: true,
            showModalGuide: false,
            showDialogVoided: false,
            resource: "purchases",
            recordId: null,
            showDialogOptions: false,
            showDialogPurchasePayments: false,
            showImportDialog: false,
            columns: {
                date_of_due: {
                    title: "F. Vencimiento",
                    visible: false
                },
                total_free: {
                    title: "T.Gratuita",
                    visible: false
                },
                total_unaffected: {
                    title: "T.Inafecta",
                    visible: false
                },
                total_exonerated: {
                    title: "T.Exonerado",
                    visible: false
                },
                total_taxed: {
                    title: "T.Gravado",
                    visible: false
                },
                total_igv: {
                    title: "T.Igv",
                    visible: false
                },
                total_perception: {
                    title: "Percepcion",
                    visible: false
                },
                guides: {
                    title: "Guias",
                    visible: false
                },
                purchase_order: {
                    title: "Orden de Compra",
                    visible: false
                }
            },
            permissions: {}
        };
    },
    computed: {
        ...mapState(["document_types_guide", "warehouses"])
    },
    async created() {
        this.loadColumnVisibility();
        this.$store.commit("setConfiguration", this.configuration);
        this.$store.commit("setTypeUser", this.typeUser);
        await this.$http.get(`/${this.resource}/tables`).then(response => {
            this.permissions = response.data.permissions;
        });
        this.getDocumentTypes();
    },
    methods: {
        formatDate(date) {
            if (!date) return null;
            return moment(date).format("DD-MM-YYYY");
        },
        saveColumnVisibility() {
            localStorage.setItem(
                "columnVisibilityPurchases",
                JSON.stringify(this.columns)
            );
        },
        loadColumnVisibility() {
            const savedColumns = localStorage.getItem(
                "columnVisibilityPurchases"
            );
            if (savedColumns) {
                this.columns = JSON.parse(savedColumns);
            }
        },
        getItemDescription(scope) {
            return scope.row.name_product_pdf
                ? scope.row.name_product_pdf
                : scope.row.description;
        },
        ...mapActions(["loadConfiguration"]),
        clickPurchasePayment(recordId) {
            this.recordId = recordId;
            this.showDialogPurchasePayments = true;
        },
        clickVoided(recordId = null) {
            this.recordId = recordId;
            this.showDialogVoided = true;
        },
        clickDownload(download) {
            window.open(download, "_blank");
        },
        clickOptions(recordId = null) {
            this.recordId = recordId;
            this.showDialogOptions = true;
        },
        clickGuide(recordId = null) {
            this.recordId = recordId;
            this.showModalGuide = true;
        },
        clickAnulate(id) {
            this.anular(`/${this.resource}/anular/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickDelete(id) {
            this.delete(`/${this.resource}/delete/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        clickImport() {
            this.showImportDialog = true;
        },
        getDocumentTypes() {
            this.$http
                .post("/dispatches/getDocumentType")
                .then(result => {
                    this.$store.commit("setDocumentTypesGuide", result.data);
                })
                .then(() => {
                    this.disableGuideBtn = !this.disableGuideBtn;
                });
        },
        isDateWarning(date_due) {
            let today = Date.now();
            return moment(date_due).isBefore(today);
        }
    }
};
</script>
