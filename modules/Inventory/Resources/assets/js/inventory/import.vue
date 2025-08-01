<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create" class="dialog-import">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <div class="row">
                    <div class="col-12 form-group">
                        <el-checkbox v-model="stock_establishments">
                            Stock masivos para los establecimientos
                        </el-checkbox>
                    </div>
                    <div v-if="!stock_establishments" class="col-12 form-group" :class="{'has-danger': errors.warehouse_id}">
                        <label for="warehouse">Almacén</label>
                        <el-select v-model="form.warehouse_id">
                            <el-option v-for="w in warehouses" :key="w.id" :label="w.description" :value="w.id"></el-option>
                        </el-select>
                        <small class="form-control-feedback" v-if="errors.warehouse_id" v-text="errors.warehouse_id[0]"></small>
                    </div>
                    <div class="col-12 form-group" :class="{'has-danger': errors.file}">
                        <el-upload
                                ref="upload"
                                :headers="headers"
                                :action="actionsUrl"
                                :show-file-list="true"
                                :auto-upload="false"
                                :multiple="false"
                                :on-error="errorUpload"
                                :before-upload="onBeforeUpload"
                                :limit="1"
                                :data="form"
                                :on-success="successUpload">
                            <el-button slot="trigger" type="primary">Seleccione un archivo (xlsx)</el-button>
                        </el-upload>
                        <small class="form-control-feedback" v-if="errors.file" v-text="errors.file[0]"></small>
                    </div>
                    <div v-if="!stock_establishments" class="col-12 mt-4 mb-2">
                        <a class="text-dark mr-auto" href="/formats/stock_real.xlsx" target="_new">
                            <span class="mr-2">Descargar formato de ejemplo para importar</span>
                            <i class="fa fa-download"></i>
                        </a>
                    </div>
                    <div v-else  class="col-12 mt-4 mb-2">
                        <a class="text-dark mr-auto" @click="downloadFormat()" href="/" target="_new">
                            <span class="mr-2">Descargar formato de ejemplo (stock establecimientos)</span>
                            <i class="fa fa-download"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="form-actions text-right mt-5">
                <el-button class="second-buton" @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Procesar</el-button>
            </div>
        </form>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog'],
        data() {
            return {
                loading_submit: false,
                headers: headers_token,
                titleDialog: null,
                resource: 'inventory',
                stock_establishments: false,
                errors: {},
                form: {},
                warehouses: []
            }
        },
        computed:{
            actionsUrl() {
                return this.stock_establishments ? '/inventory/import/stock-establishments' : "/inventory/import"
            }
        },
        async created() {
            this.initForm();
            await this.onFetchTables();
        },
        methods: {
            onBeforeUpload(file) {},
            async onFetchTables() {
                this.loading_submit = true;
                await this.$http.get('/items/import/tables').then(response => {
                    this.warehouses = response.data.warehouses;
                }).finally(() => this.loading_submit = false);
            },
            initForm() {
                this.errors = {}
                this.form = {
                    warehouse_id: null
                }
                this.stock_establishments = false
            },
            create() {
                this.titleDialog = 'Importar Ajuste de Stock'
            },
            async submit() {
                if (!this.stock_establishments && !this.form.warehouse_id) {
                    this.$message.warning('Seleccione un almacén para poder continuar');
                    return;
                }
                this.loading_submit = true
                await this.$refs.upload.submit()
                this.loading_submit = false
            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            successUpload(response, file, fileList) {
                if (response.success) {
                    this.$message.success(response.message)
                    this.$eventHub.$emit('reloadData')
                    this.$eventHub.$emit('reloadTables')
                    this.$refs.upload.clearFiles()
                    this.close()
                } else {
                    this.$message({message:response.message, type: 'error'})
                }
            },
            errorUpload(error) {
                console.log(error)
            },
            downloadFormat() {
                window.open(`/${this.resource}/stock-establishments-format/export`, '_blank');
            }

        }
    }
</script>
