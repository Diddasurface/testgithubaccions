<template>
    <div>
        <div class="page-header pr-0">
            <h2><a href="/item-lots">
                <svg  xmlns="http://www.w3.org/2000/svg" style="margin-top: -5px;"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-category-2"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 4h6v6h-6z" /><path d="M4 14h6v6h-6z" /><path d="M17 17m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /><path d="M7 7m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0" /></svg>
            </a></h2>
            <ol class="breadcrumbs">
                <li class="active"><span>{{ title }}</span></li>
            </ol>
            <div class="right-wrapper pull-right">
                <button type="button" class="btn btn-success btn-sm  mt-2 mr-2" @click.prevent="clickExport()"><i class="fa fa-file-excel"></i> Exportar</button>
            </div>
        </div>
        <div class="card tab-content-default row-new mb-0">
            <!-- <div class="card-header bg-info">
                <h3 class="my-0">Listado de {{ title }}</h3>
            </div> -->
            <div class="card-body">
             
                <div v-loading="loading_submit">
                    <div class="row ">   
                        <div class="col-md-12 col-lg-12 col-xl-12 filter-container">
                            <div class="btn-filter-content">
                                <el-button
                                    type="primary"
                                    class="btn-show-filter mb-2"
                                    :class="{ shift: isVisible }"
                                    @click="toggleInformation"
                                >
                                    {{ isVisible ? "Ocultar filtros" : "Mostrar filtros" }}
                                </el-button>
                            </div>
                            <div class="row" v-if="isVisible">
                                <div class="col-lg-4 col-md-4 col-sm-12 pb-2">
                                    <div class="d-flex">
                                        <div class="d-flex align-items-center" style="width:100px">
                                            Filtrar por:
                                        </div>
                                        <el-select v-model="search.column"  placeholder="Select" @change="changeClearInput">
                                            <el-option v-for="(label, key) in columns" :key="key" :value="key" :label="label"></el-option>
                                        </el-select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-4 col-sm-12 pb-2">
                                    <template v-if="search.column=='date'">
                                        <el-date-picker
                                            v-model="search.value"
                                            type="date"
                                            style="width: 100%;"
                                            placeholder="Buscar"
                                            value-format="yyyy-MM-dd"
                                            @change="getRecords">
                                        </el-date-picker>
                                    </template>
                                    <template v-else>
                                        <el-input placeholder="Buscar"
                                            v-model="search.value"
                                            style="width: 100%;"
                                            prefix-icon="el-icon-search"
                                            @input="getRecords">
                                        </el-input>
                                    </template>
                                </div>
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
                                            <th>Serie</th>
                                            <th>Producto</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                            <th>Vendido</th>
                                            <th class="text-right">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="(row, index) in records" :key="index">
                                            <!-- <td>{{ customIndex(index) }}</td> -->
                                            <td>{{ row.series }}</td>
                                            <td>{{ row.item_description }}</td>
                                            <td>{{ row.date }}</td>
                                            <td>{{ row.state }}</td>
                                            <td>{{ row.status }}</td>
                                            <td class="text-right">
                                                <button type="button" class="btn waves-effect waves-light btn-xs btn-info" @click.prevent="clickCreate(row.id)" v-if="!row.has_sale">Editar</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div>
                                    <el-pagination
                                            @current-change="getRecords"
                                            layout="total, prev, pager, next"
                                            :total="pagination.total"
                                            :current-page.sync="pagination.current_page"
                                            :page-size="pagination.per_page">
                                    </el-pagination>
                                </div>
                            </div>
                        </div>
 
                    </div>
                </div>
            </div>

            <item-lot-form 
                :showDialog.sync="showDialog"
                :recordId="recordId"
                    ></item-lot-form> 
        </div>
    </div>
</template>
<style>
@media only screen and (max-width: 485px){
    .filter-container{
      margin-top: 0px;
      & .btn-filter-content, .btn-container-mobile{
        display: flex;
        align-items: center;
        justify-content: start;
      }
    }
}
</style>
<script>

    import ItemLotForm from './form.vue' 
    import queryString from 'query-string'

    export default {
        components: {ItemLotForm},
        data() {
            return {
                title: null,
                showDialog: false, 
                resource: 'item-lots',
                recordId: null,
                search: {
                    column: null,
                    value: null
                },
                columns: [],
                records: [],
                pagination: {},
                isVisible: false,
                loading_submit: false,
                showLeftShadow: false,
                showRightShadow: false,
            }
        },
        async mounted () {
            let column_resource = _.split(this.resource, '/')
            await this.$http.get(`/${_.head(column_resource)}/columns`).then((response) => {
                this.columns = response.data
                this.search.column = _.head(Object.keys(this.columns))
            });
            await this.getRecords()

            this.$nextTick(() => {
                const el = this.$refs.scrollContainer;
                if (el) {
                    el.addEventListener('scroll', this.checkScrollShadows);
                    this.checkScrollShadows();
                }
            });

        },
        created() {

            this.title = 'Series'

            this.$eventHub.$on('reloadData', () => {
                this.getRecords()
            })

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
            toggleInformation() {
                this.isVisible = !this.isVisible;
            },
            customIndex(index) {
                return (this.pagination.per_page * (this.pagination.current_page - 1)) + index + 1
            },
            getRecords() {

                this.loading_submit = true

                return this.$http.get(`/${this.resource}/records?${this.getQueryParameters()}`).then((response) => {

                    this.records = response.data.data
                    this.pagination = response.data.meta
                    this.pagination.per_page = parseInt(response.data.meta.per_page)

                })
                .catch(error => {
                })
                .then(() => {
                    this.loading_submit = false
                });
            },
            getQueryParameters() {
                return queryString.stringify({
                    page: this.pagination.current_page,
                    limit: this.limit,
                    ...this.search
                })
            },
            changeClearInput(){
                this.search.value = ''
                this.getRecords()
            },
            clickExport(){

                let query = queryString.stringify({
                    ...this.search
                })

                window.open(`/${this.resource}/export?${query}`, '_blank');

            },
            clickCreate(recordId = null) {
                this.recordId = recordId
                this.showDialog = true
            }
        }
    }
</script>
