<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/order-notes"
                    ><svg
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
                        class="icon icon-tabler icons-tabler-outline icon-tabler-edit"
                    >
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <path
                            d="M7 7h-1a2 2 0 0 0 -2 2v9a2 2 0 0 0 2 2h9a2 2 0 0 0 2 -2v-1"
                        />
                        <path
                            d="M20.385 6.585a2.1 2.1 0 0 0 -2.97 -2.97l-8.415 8.385v3h3l8.385 -8.415z"
                        />
                        <path d="M16 5l3 3" /></svg
                ></a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>Pedidos</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <a
                    :href="`/${resource}/create`"
                    class="btn btn-custom btn-sm  mt-2 mr-2"
                    ><i class="fa fa-plus-circle"></i> Nuevo</a
                >
            </div>

            <div
                class="btn-group flex-wrap pull-right"
                v-if="config.mi_tienda_pe === true"
            >
                <button
                    aria-expanded="false"
                    class="btn btn-custom btn-sm mt-2 mr-2 dropdown-toggle"
                    data-toggle="dropdown"
                    type="button"
                >
                    <i class="fa fa-upload"></i>
                    Importar
                    <span class="caret"></span>
                </button>
                <div
                    class="dropdown-menu"
                    role="menu"
                    style="
                                position: absolute;
                                will-change: transform;
                                top: 0px;
                                left: 0px;
                                transform: translate3d(0px, 42px, 0px);
                            "
                    x-placement="bottom-start"
                >
                    <a
                        class="dropdown-item text-1"
                        href="#"
                        @click.prevent="clickImport()"
                    >
                        MiTienda.Pe
                    </a>
                </div>
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
                <data-table
                    :resource="resource"
                    :typeUser="typeUser"
                    :soapCompany="soapCompany"
                >
                    <tr slot="heading">
                        <!-- <th>#</th> -->
                        <th class="text-left">Fecha Emisión</th>
                        <th
                            class="text-center"
                            v-if="columns.delivery_date.visible"
                        >
                            Fecha Entrega
                        </th>
                        <th>Vendedor</th>
                        <th>Cliente</th>
                        <th>Estado</th>
                        <th>Pedido</th>
                        <th>Comprobantes</th>
                        <th v-if="columns.sale_notes.visible">
                            Notas de venta
                        </th>
                        <th v-if="columns.quotation.visible">Cotizacion</th>
                        <th v-if="columns.dispatches.visible">Guías</th>
                        <th v-if="columns.mi_tienda_pe.visible">
                            #Pedido MiTienda.Pe
                        </th>
                        <!-- <th>Estado</th> -->
                        <th class="text-center">Moneda</th>
                        <th
                            class="text-right"
                            v-if="columns.total_exportation.visible"
                        >
                            T.Exportación
                        </th>
                        <th
                            class="text-right"
                            v-if="columns.total_unaffected.visible"
                        >
                            T.Inafecta
                        </th>
                        <th
                            class="text-right"
                            v-if="columns.total_exonerated.visible"
                        >
                            T.Exonerado
                        </th>
                        <th
                            class="text-right"
                            v-if="columns.total_taxed.visible"
                        >
                            T.Gravado
                        </th>
                        <th class="text-right" v-if="columns.total_igv.visible">
                            T.Igv
                        </th>
                        <th class="text-right">Saldo</th>
                        <th class="text-right">Total</th>
                        <th class="text-center">PDF</th>
                        <th class="text-right">Acciones</th>
                    </tr>

                    <tr></tr>
                    <tr
                        slot-scope="{ index, row }"
                        :class="{ anulate_color: row.state_type_id == '11' }"
                    >
                        <!-- <td>{{ index }}</td> -->
                        <td class="text-left">
                            {{ formatDate(row.date_of_issue) }}
                        </td>
                        <td
                            class="text-center"
                            v-if="columns.delivery_date.visible"
                        >
                            {{ formatDate(row.delivery_date) }}
                        </td>
                        <td>{{ row.user_name }}</td>
                        <td>
                            {{ row.customer_name }}<br /><small
                                v-text="row.customer_number"
                            ></small>
                        </td>
                        <td>
                            <StateType
                                :key="'state_type_' + row.id"
                                :id="row.state_type_id"
                                :description="row.state_type_description"
                            />
                        </td>
                        <td>{{ row.identifier }}</td>
                        <td>
                            <template v-for="(document, i) in row.documents">
                                <label
                                    :key="i"
                                    v-text="showAnulateDoc(document)"
                                    class="d-block"
                                ></label>
                            </template>
                        </td>
                        <td v-if="columns.sale_notes.visible">
                            <template v-for="(sale_note, i) in row.sale_notes">
                                <label
                                    :key="i"
                                    v-text="sale_note.number_full"
                                    class="d-block"
                                ></label>
                            </template>
                        </td>

                        <td v-if="columns.quotation.visible">
                            <!-- Cotizacion -->
                            <template
                                v-if="
                                    row.quotation !== undefined &&
                                        row.quotation.full_number !== undefined
                                "
                            >
                                <label class="d-block"
                                    >{{ row.quotation.full_number }}
                                </label>
                            </template>
                        </td>
                        <td v-if="columns.dispatches.visible">
                            <!-- Pedidos -->
                            <template v-for="(dispach, i) in row.dispatches">
                                <label
                                    :key="i"
                                    v-text="dispach.number"
                                    class="d-block"
                                ></label>
                            </template>
                        </td>

                        <td v-if="columns.mi_tienda_pe.visible">
                            <!-- Codigo mi tienda -->
                            <template
                                v-if="
                                    row.mi_tienda_pe &&
                                        row.mi_tienda_pe.order_number
                                "
                            >
                                {{ row.mi_tienda_pe.order_number }}
                            </template>
                        </td>

                        <!-- <td>{{ row.state_type_description }}</td> -->
                        <td class="text-center">{{ row.currency_type_id }}</td>
                        <td
                            class="text-right"
                            v-if="columns.total_exportation.visible"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_exportation }}
                        </td>
                        <td
                            class="text-right"
                            v-if="columns.total_unaffected.visible"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_unaffected }}
                        </td>
                        <td
                            class="text-right"
                            v-if="columns.total_exonerated.visible"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_exonerated }}
                        </td>
                        <td
                            class="text-right"
                            v-if="columns.total_taxed.visible"
                        >
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_taxed }}
                        </td>
                        <td class="text-right" v-if="columns.total_igv.visible">
                            {{row.currency_type_id === 'PEN' ? 'S/' : '$'}}
                            {{ row.total_igv }}
                        </td>
                        <td class="text-right">
                            <label
                                v-if="row.documents.length > 0"
                                :key="'doc_payment_' + index"
                                v-text="calculatePayments(row.documents)"
                            ></label>
                            <label
                                v-if="row.sale_notes.length > 0"
                                :key="'sale_note_payment_' + index"
                                v-text="calculatePayments(row.sale_notes)"
                            ></label>
                        </td>
                        <td class="text-right">{{row.currency_type_id === 'PEN' ? 'S/' : '$'}} {{ row.total }}</td>
                        <td class="text-right">
                            <button
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info"
                                @click.prevent="clickOptionsPdf(row.id)"
                            >
                                PDF
                            </button>
                        </td>

                        <td class="text-right">
                            <button
                                v-if="
                                    row.state_type_id != '11' &&
                                        row.btn_generate &&
                                        seller_can_generate_cpe === true &&
                                        soapCompany != '03'
                                "
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info"
                                @click.prevent="clickOptions(row.id)"
                            >
                                Generar comprobante
                            </button>

                            <a
                                v-if="cantEdited(row)"
                                :href="`/${resource}/edit/${row.id}`"
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info"
                                >Editar</a
                            >
                            <button
                                v-if="canAnulate(row)"
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-danger"
                                @click.prevent="clickAnulate(row.id)"
                            >
                                Anular
                            </button>
                            <button
                                @click="duplicate(row.id)"
                                type="button"
                                class="btn waves-effect waves-light btn-xs btn-info"
                            >
                                Duplicar
                            </button>
                            <a
                                :href="
                                    `/dispatches/create_new/order_note/${
                                        row.id
                                    }`
                                "
                                class="btn waves-effect waves-light btn-xs btn-warning m-1__2"
                                >Guía</a
                            >

                            <ChangeStateType
                                :key="'change_state_type_' + row.id"
                                :state="row.state_type_id"
                                :id="row.id"
                                v-if="config.order_node_advanced"
                            />
                        </td>
                    </tr>
                </data-table>
            </div>

            <quotation-options
                :showDialog.sync="showDialogOptions"
                :recordId="recordId"
                :showGenerate="true"
                :showClose="true"
                :configuration="configuration"
            ></quotation-options>

            <quotation-options-pdf
                :showDialog.sync="showDialogOptionsPdf"
                :recordId="recordId"
                :showClose="true"
                :configuration="configuration"
            ></quotation-options-pdf>

            <mi-tienda-pe
                :showDialog.sync="showMiTiendaPeDialog"
            ></mi-tienda-pe>
        </div>
    </div>
</template>
<style scoped>
.anulate_color {
    color: red;
}
</style>
<script>
import QuotationOptions from "./partials/options.vue";
import QuotationOptionsPdf from "./partials/options_pdf.vue";
import MiTiendaPe from "./mi_tienda_pe.vue";
import DataTable from "../../components/DataTable.vue";
import { deletable } from "@mixins/deletable";
import { mapActions, mapState } from "vuex";

import StateType from "../../../../../../OrderNote/Resources/assets/js/components/StateType.vue";
import ChangeStateType from "../../../../../../OrderNote/Resources/assets/js/components/ChangeStateType.vue";

export default {
    props: ["typeUser", "soapCompany", "configuration"],
    mixins: [deletable],
    components: {
        DataTable,
        QuotationOptions,
        MiTiendaPe,
        QuotationOptionsPdf,
        StateType,
        ChangeStateType
    },
    created() {
        this.loadColumnVisibility();
        this.$store.commit("setConfiguration", this.configuration);
        this.loadConfiguration();
        if (this.config.mi_tienda_pe === true) {
            this.getMiTiendaDataData();
        }
    },
    data() {
        return {
            resource: "order-notes",
            recordId: null,
            showMiTiendaPeDialog: false,
            showDialogOptions: false,
            showDialogOptionsPdf: false,
            columns: {
                total_exportation: {
                    title: "T.Exportación",
                    visible: false
                },
                total_unaffected: {
                    title: "T.Inafecto",
                    visible: false
                },
                total_exonerated: {
                    title: "T.Exonerado",
                    visible: false
                },
                total_taxed: {
                    title: "T.Gravado",
                    visible: true
                },
                total_igv: {
                    title: "T.IGV",
                    visible: true
                },
                delivery_date: {
                    title: "F.Entrega",
                    visible: true
                },
                sale_notes: {
                    title: "Notas de venta",
                    visible: true
                },
                quotation: {
                    title: "Cotizacion",
                    visible: false
                },
                dispatches: {
                    title: "Guías de Remisión",
                    visible: false
                },
                mi_tienda_pe: {
                    title: "Pedido MiTienda.Pe",
                    visible: false
                }
            },
            state_type_accepted: ["01", "03", "05", "07", "13"]
        };
    },
    computed: {
        ...mapState(["config", "mi_tienda_pe"]),
        seller_can_generate_cpe() {
            if (
                this.typeUser === "admin" ||
                (this.configuration !== undefined &&
                    this.configuration !== null &&
                    this.configuration
                        .seller_can_generate_sale_opportunities === true)
            ) {
                return true;
            }
            return false;
        }
    },
    methods: {
        formatDate(date) {
            if (!date) return null;
            return moment(date).format("DD-MM-YYYY");
        },
        saveColumnVisibility() {
            localStorage.setItem(
                "columnVisibilityOrders",
                JSON.stringify(this.columns)
            );
        },
        loadColumnVisibility() {
            const savedColumns = localStorage.getItem("columnVisibilityOrders");
            if (savedColumns) {
                this.columns = JSON.parse(savedColumns);
            }
        },
        ...mapActions(["loadConfiguration"]),

        clickImport() {
            this.showMiTiendaPeDialog = true;
        },
        cantEdited(row) {
            if (
                row &&
                row.documents &&
                row.documents.length == 0 &&
                row.state_type_id != "11"
            )
                return true;
            return false;
        },
        showAnulateDoc(document) {
            if (document.state_type_id == "11")
                return document.number_full + " (Anulado)";
            return document.number_full;
        },
        canAnulate(row) {
            if (row.state_type_id === "11") return false;

            if (row && row.documents) {
                if (row.documents.length == 0 && row.state_type_id != "11")
                    return true;

                if (row.documents.length > 0) {
                    let can_anulate = false;

                    const exist_accepted_document = row.documents.find(
                        document => {
                            return this.state_type_accepted.includes(
                                document.state_type_id
                            );
                        }
                    );

                    if (!exist_accepted_document) can_anulate = true;

                    return can_anulate;
                }
            }

            return false;

            // if(
            //     row &&
            //     row.documents
            // ) {
            //     if (
            //         row.documents.length == 0 &&
            //         row.state_type_id != '11'
            //     ) return true;
            //     if (
            //         row.documents.length > 0
            //     ) {
            //         let sal = false;
            //         row.documents.forEach(function(doc){
            //             sal = doc.state_type_id === '11';
            //         })
            //         return sal;
            //     }
            // }
        },
        clickEdit(id) {
            this.recordId = id;
            this.showDialogFormEdit = true;
        },
        clickOptions(recordId = null) {
            this.recordId = recordId;
            this.showDialogOptions = true;
        },
        clickOptionsPdf(recordId = null) {
            this.recordId = recordId;
            this.showDialogOptionsPdf = true;
        },
        clickAnulate(id) {
            this.voided(`/${this.resource}/voided/${id}`).then(() =>
                this.$eventHub.$emit("reloadData")
            );
        },
        duplicate(id) {
            this.$http
                .post(`${this.resource}/duplicate`, { id })
                .then(response => {
                    if (response.data.success) {
                        this.$message.success(
                            "Se guardaron los cambios correctamente."
                        );
                        this.$eventHub.$emit("reloadData");
                    } else {
                        this.$message.error("No se guardaron los cambios");
                    }
                })
                .catch(error => {});
            this.$eventHub.$emit("reloadData");
        },
        getMiTiendaDataData() {
            this.$http.post(`/mi_tienda_pe/getdata`).then(response => {
                let data = response.data;
                this.$store.commit("setMiTiendaPe", data.configurationMiTienda);
            });
        },
        calculatePayments(documents) {
            let payments = 0;
            documents.forEach(doc => {
                payments = payments + doc.total_payments;
            });

            return payments.toFixed(2);
        }
    }
};
</script>
