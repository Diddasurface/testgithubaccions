<template>
    <div>
        <div class="page-header pr-0">
            <h2>
                <a href="/documentary-procedure/processes">
                    <svg xmlns="http://www.w3.org/2000/svg"
                        style="margin-top: -5px;"
                        width="24"
                        height="24"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        class="feather feather-folder">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </a>
            </h2>
            <ol class="breadcrumbs">
                <li class="active"><span>TIPOS DE TRÁMITES</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <div class="btn-group flex-wrap">
                    <button
                        class="btn btn-custom btn-sm mt-2 mr-2"
                        type="button"
                        @click="onCreate"
                    >
                        <i class="fa fa-plus-circle"></i> Nuevo
                    </button>
                </div>
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de tipos de trámites</h3>
            </div> -->
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-2 mb-3">
                        <form class="form-group"
                              @submit.prevent="onFilter">
                            <div class="input-group mb-3">
                                <input
                                    v-model="filter.name"
                                    class="form-control form-control-default"
                                    placeholder="Filtrar por nombre"
                                    type="text"
                                />
                                <div class="input-group-append">
                                    <button
                                        class="btn btn-search-default btn-outline-secondary"
                                        style="border-color: #CED4DA"
                                        type="submit"
                                    >
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-12">
                <div class="scroll-shadow shadow-left" v-show="showLeftShadow"></div>
                <div class="scroll-shadow shadow-right" v-show="showRightShadow"></div>
                <div class="table-responsive" ref="scrollContainer">
                    <table class="table">
                        <thead>
                        <tr>
                            <!-- <th>#</th> -->
                            <th>Trámite</th>
                            <th>Descripción</th>
                            <th>Terminos y condiciones</th>
                            <th>Activo</th>
                            <th class="text-right">Precio</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr
                            v-for="(item,index) in items"
                            :key="item.id"
                            :class="{ 'table-danger': !item.active }"
                        >
                            <!-- <td class="text-left">{{ index + 1 }}</td> -->
                            <td>
                                <el-tooltip v-if="item.requirements && item.requirements.length > 0"
                                            placement="right-start">
                                    <div slot="content">
                                        Requisitos:
                                        <ul v-for="(requirement) in item.requirements">
                                            <li>
                                                {{ requirement.requirement_name }}
                                            </li>
                                        </ul>

                                    </div>
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                                {{ item.name }}
                            </td>
                            <td>{{ item.description }}</td>
                            <td>

                                <!--
                                <el-tooltip v-if="item.documentary_terms && item.documentary_terms.length > 0"
                                            placement="right-start">
                                    <div slot="content">
                                        Requisitos:
                                        <ul v-for="(requirement) in item.documentary_terms">
                                            <li>
                                                {{ requirement.term_name }}
                                            </li>
                                        </ul>

                                    </div>
                                    <i class="fa fa-info-circle"></i>
                                </el-tooltip>
                                {{ item.name }}
                                -->

                                <ul v-for="(requirement) in item.documentary_terms">
                                    <li>
                                        {{ requirement.term_name }}
                                    </li>
                                </ul>


                            </td>

                            <td class="text-left">
                                <span v-if="item.active">Si</span>
                                <span v-else>No</span>
                            </td>
                            <td class="text-right">{{ item.price }}</td>
                            <td class="text-right">
                                <el-button
                                    :disabled="loading"
                                    type="success"
                                    @click="onEdit(item)"
                                >
                                    <i class="fa fa-edit"></i>
                                </el-button>
                                <el-button
                                    :disabled="loading"
                                    type="danger"
                                    @click="onDelete(item)"
                                >
                                    <i class="fa fa-trash"></i>
                                </el-button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </div>
        </div>
        <ModalAddEdit
            :process="process"
            :requirements="requirements"
            :stages="stages"
            :visible.sync="openModalAddEdit"
            @onAddItem="onAddItem"
            @onUpdateItem="onUpdateItem"
        ></ModalAddEdit>
    </div>
</template>

<script>
import ModalAddEdit from "./ModalAddEdit";

export default {
    props: {
        processes: {
            type: Array,
            required: true,
        },
        stages: {
            type: Array,
            required: true,
        },
        requirements: {
            type: Array,
            required: true,
        },
    },
    components: {
        ModalAddEdit,
    },
    data() {
        return {
            items: [],
            process: null,
            openModalAddEdit: false,
            loading: false,
            filter: {
                name: "",
            },
            basePath: '/documentary-procedure/processes',
            showLeftShadow: false,
            showRightShadow: false,
        };
    },
    mounted() {
        this.items = this.processes;

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
        onFilter() {
            this.loading = true;
            const params = this.filter;
            this.$http
                .get(this.basePath, {params})
                .then((response) => {
                    this.items = response.data.data;
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        onDelete(item) {
            this.$confirm(
                `¿estás seguro de eliminar al elemento ${item.name}?`,
                "Atención",
                {
                    confirmButtonText: "Si, continuar",
                    cancelButtonText: "No, cerrar",
                    type: "warning",
                }
            )
                .then(() => {
                    this.$http
                        .delete(`${this.basePath}/${item.id}/delete`)
                        .then((response) => {
                            this.$message({
                                type: "success",
                                message: response.data.message,
                            });
                            this.items = this.items.filter((i) => i.id !== item.id);
                        })
                        .catch((error) => {
                            this.axiosError(error);
                        });
                })
                .catch();
        },
        onEdit(item) {
            this.process = {...item};
            this.openModalAddEdit = true;
        },
        onUpdateItem(data) {
            this.items = this.items.map((i) => {
                if (i.id === data.id) {
                    return data;
                }
                return i;
            });
            this.onFilter()
        },
        onAddItem(data) {
            this.items.unshift(data);
        },
        onCreate() {
            this.process = null;
            this.openModalAddEdit = true;
        },
    },
};
</script>
