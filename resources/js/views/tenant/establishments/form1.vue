<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @close="close" @open="create">
        <form autocomplete="off" @submit.prevent="submit">
            <div class="form-body">
                <el-tabs v-model="activeName">
                    <el-tab-pane class name="first">
                        <span slot="label">Datos de sucursal</span>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group" :class="{'has-danger': errors.description}">
                                    <label class="control-label">Descripción</label>
                                    <el-input v-model="form.description"></el-input>
                                    <small class="form-control-feedback" v-if="errors.description" v-text="errors.description[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.code}">
                                    <label class="control-label">Código Domicilio Fiscal</label>
                                    <el-input v-model="form.code" :maxlength="4"></el-input>
                                    <small class="form-control-feedback" v-if="errors.code" v-text="errors.code[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.country_id}">
                                    <label class="control-label">País</label>
                                    <el-select v-model="form.country_id" filterable>
                                        <el-option v-for="option in countries" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.country_id" v-text="errors.country_id[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.department_id}">
                                    <label class="control-label">Departamento</label>
                                    <el-select v-model="form.department_id" filterable @change="filterProvince">
                                        <el-option v-for="option in all_departments" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.department_id" v-text="errors.department_id[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.province_id}">
                                    <label class="control-label">Provincia</label>
                                    <el-select v-model="form.province_id" filterable @change="filterDistrict">
                                        <el-option v-for="option in provinces" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.province_id" v-text="errors.province_id[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.province_id}">
                                    <label class="control-label">Distrito</label>
                                    <el-select v-model="form.district_id" filterable>
                                        <el-option v-for="option in districts" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>
                                    <small class="form-control-feedback" v-if="errors.district_id" v-text="errors.district_id[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group" :class="{'has-danger': errors.address}">
                                    <label class="control-label">Dirección Fiscal</label>
                                    <el-input v-model="form.address"></el-input>
                                    <small class="form-control-feedback" v-if="errors.address" v-text="errors.address[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group" :class="{'has-danger': errors.telephone}">
                                    <label class="control-label">Teléfono</label>
                                    <el-input 
                                        v-model="form.telephone" 
                                        @input="validateNumericInput('telephone')">
                                    </el-input>
                                    <small class="form-control-feedback" v-if="errors.telephone" v-text="errors.telephone[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group" :class="{'has-danger': errors.trade_address}">
                                    <label class="control-label">Dirección Comercial</label>
                                    <el-input v-model="form.trade_address"></el-input>
                                    <small class="form-control-feedback" v-if="errors.trade_address" v-text="errors.trade_address[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.email}">
                                    <label class="control-label">Correo de contacto</label>
                                    <el-input v-model="form.email"></el-input>
                                    <small class="form-control-feedback" v-if="errors.email" v-text="errors.email[0]"></small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group" :class="{'has-danger': errors.web_address}">
                                    <label class="control-label">Dirección web</label>
                                    <el-input v-model="form.web_address"></el-input>
                                    <small class="form-control-feedback" v-if="errors.web_address" v-text="errors.web_address[0]"></small>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group" :class="{'has-danger': errors.aditional_information}">
                                    <label class="control-label">Información adicional</label>
                                    <el-input v-model="form.aditional_information"></el-input>
                                    <small class="form-control-feedback" v-if="errors.aditional_information" v-text="errors.aditional_information[0]"></small>
                                </div>
                            </div>

                            <div class="col-lg-6 col-md-6">
                                <div class="form-group">
                                    <label class="control-label">
                                        Cliente por defecto
                                        <el-tooltip class="item" effect="dark" content="Aplica para Nuevo CPE" placement="top">
                                            <i class="fa fa-info-circle"></i>
                                        </el-tooltip>
                                    </label>

                                    <el-select v-model="form.customer_id" filterable remote  popper-class="el-select-customers"  clearable
                                        placeholder="Nombre o número de documento"
                                        :remote-method="searchRemoteCustomers"
                                        :loading="loading_search">
                                        <el-option v-for="option in customers" :key="option.id" :value="option.id" :label="option.description"></el-option>
                                    </el-select>

                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 form-group" style="padding-top: 29px;">
                                <img v-if="preview" :src="preview" alt="Vista previa" class="img-fluid img-thumbnail mb-2">
                                <input type="file" ref="inputFile" class="hidden" @change="onSelectImage" accept="image/png, image/jpeg, image/jpg">
                                <span class="text-muted">Se recomienda resoluciones 700x300</span>
                                <el-button class="btn-add-logo" @click="onOpenFileLogo">Cambiar logo del sucursal</el-button>
                            </div>
                            <div class="col-12">
                                <div class="form-comtrol">
                                    <el-checkbox v-model="form.has_igv_31556">
                                        Sujeto al IGV - Ley 31556
                                    </el-checkbox>
                                </div>
                            </div>
                        </div>
                    </el-tab-pane>
                    <el-tab-pane class name="second">
                        <span slot="label">Dirección secundaria</span>
                            <div class="row m-t-10">
                                <div class="col-md-12 text-center">
                                    <el-button class="btn-add-address" icon="el-icon-plus"
                                            size="mini"
                                            @click.prevent="clickAddAddress()">
                                        Agregar dirección
                                    </el-button>
                                </div>
                            </div>
                            <div v-for="(row, index) in form.addresses"
                                class="row m-t-10">
                                <div class="col-md-12">
                                    <label
                                        class="control-label">
                                        Dirección secundaria #{{ index + 1 }}
                                        <el-button class="second-buton btn-default-danger"
                                                icon="el-icon-minus"
                                                size="mini"
                                                @click.prevent="clickRemoveAddress(index)">Eliminar dirección
                                        </el-button>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <div :class="{'has-danger': errors.country_id}"
                                        class="form-group">
                                        <label class="control-label">País</label>
                                        <el-select v-model="row.country_id"
                                                filterable>
                                            <el-option v-for="option in countries"
                                                    :key="option.id"
                                                    :label="option.description"
                                                    :value="option.id"></el-option>
                                        </el-select>
                                        <small v-if="errors.country_id"
                                            class="form-control-feedback"
                                            v-text="errors.country_id[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-8">
                                    <div :class="{'has-danger': errors.location_id}"
                                        class="form-group">
                                        <label class="control-label">Ubigeo</label>
                                        <el-cascader v-model="row.location_id"
                                                    :clearable="true"
                                                    :options="locations"
                                                    filterable></el-cascader>
                                        <small v-if="errors.location_id"
                                            class="form-control-feedback"
                                            v-text="errors.location_id[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div :class="{'has-danger': errors.address}"
                                        class="form-group">
                                        <label class="control-label">Dirección</label>
                                        <el-input v-model="row.address"></el-input>
                                        <small v-if="errors.address"
                                            class="form-control-feedback"
                                            v-text="errors.address[0]"></small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div :class="{'has-danger': errors.establishment_code}"
                                        class="form-group">
                                        <label class="control-label">Código de sucursal</label>
                                        <el-input v-model="row.establishment_code"></el-input>
                                        <small v-if="errors.establishment_code"
                                            class="form-control-feedback"
                                            v-text="errors.establishment_code[0]"></small>
                                    </div>
                                </div>
                                
                            </div>
                    </el-tab-pane>
                </el-tabs>
            </div>
            <div class="form-actions text-right mt-4">
                <el-button class="second-buton" @click.prevent="close()">Cancelar</el-button>
                <el-button type="primary" native-type="submit" :loading="loading_submit">Guardar</el-button>
            </div>
        </form>
    </el-dialog>

</template>

<script>

    export default {
        props: ['showDialog', 'recordId'],
        data() {
            return {
                loading_submit: false,
                titleDialog: null,
                resource: 'establishments',
                errors: {},
                form: {},
                countries: [],
                all_departments: [],
                all_provinces: [],
                all_districts: [],
                provinces: [],
                districts: [],
                customers: [],
                all_customers: [],
                loading_search:false,
                file: null,
                preview: null,
                activeName: 'first',
                locations: [],
                codeExists: false,
                existingCodes: [],
            }
        },
        async created() {
            await this.initForm()
            await this.loadExistingCodes()
            await this.$http.get(`/${this.resource}/tables`)
                .then(response => {
                    this.countries = response.data.countries
                    this.all_departments = response.data.departments
                    this.all_provinces = response.data.provinces
                    this.all_districts = response.data.districts
                    this.all_customers = response.data.customers
                    this.locations = response.data.locations;
                })

            await this.filterCustomers()
        },
        methods: {
            onSelectImage(event) {
                const files = event.target.files;
                if (files) {
                    const file = files[0];
                    this.preview = URL.createObjectURL(file);
                    this.file = file;
                } else {
                    this.preview = null;
                    this.file = null;
                }
            },
            onOpenFileLogo() {
                this.$refs.inputFile.click();
            },
            searchRemoteCustomers(input) {

                if (input.length > 0) {

                    this.loading_search = true
                    let parameters = `input=${input}`

                    this.$http.get(`/reports/data-table/persons/customers?${parameters}`)
                            .then(response => {
                                this.customers = response.data.persons
                                this.loading_search = false

                                if(this.customers.length == 0){
                                    this.filterCustomers()
                                }
                            })
                } else {
                    this.filterCustomers()
                }

            },
            filterCustomers() {
                this.customers = this.all_customers
            },
            initForm() {
                this.errors = {}
                this.form = {
                    id: null,
                    description: null,
                    country_id: 'PE',
                    department_id: null,
                    province_id: null,
                    district_id: null,
                    address: null,
                    addresses: [],
                    telephone: null,
                    email: null,
                    code: null,
                    trade_address: null,
                    web_address: null,
                    aditional_information: null,
                    customer_id: null,
                    has_igv_31556: false,
                }
                this.file = null;
                this.preview = null;
            },
            async create() {
                this.titleDialog = (this.recordId)? 'Editar Sucursal':'Nuevo Sucursal'
                if (this.recordId) {
                    await this.$http.get(`/${this.resource}/record/${this.recordId}`)
                        .then(response => {
                            if (response.data !== '') {
                                this.form = response.data.data;
                                this.preview = this.form.logo;
                                delete this.form.logo;
                                this.filterProvinces()
                                this.filterDistricts()
                                this.searchRemoteCustomers(this.form.customer_number)
                            }
                        })
                }
            },
            async loadExistingCodes() {
                await this.$http.get(`/${this.resource}/codes`)
                    .then(response => {
                        this.existingCodes = response.data;
                    })
                    .catch(error => {
                        console.error('Error al cargar códigos existentes:', error);
                    });
            },
            validateCodeUniqueness() {
                if (!this.form.code) return;
                
                this.codeExists = false;
                
                const exists = this.existingCodes.find(item => 
                    item.code === this.form.code && item.id !== this.form.id
                );
                
                this.codeExists = !!exists;
            },
            submit() {

                if (this.form.telephone && !/^[\d\-]+$/.test(this.form.telephone)) {
                    this.$message.error('El teléfono solo debe contener números y guiones');
                    return;
                }

                this.validateCodeUniqueness();
                
                if (this.codeExists) {
                    this.$message.error('El código de domicilio fiscal ingresado ya existe. Por favor ingrese uno nuevo');
                    return;
                }

                const data = new FormData();
                for (var key in this.form) {
                    const value = this.form[key];

                    if (Array.isArray(value)) {
                        value.forEach((item, index) => {
                            for (var subKey in item) {
                                if (subKey === 'location_id' && Array.isArray(item[subKey])) {
                                    item[subKey].forEach((locId, locIndex) => {
                                        data.append(`addresses[${index}][location_id][${locIndex}]`, locId);
                                    });
                                } else {
                                    data.append(`addresses[${index}][${subKey}]`, item[subKey]);
                                }
                            }
                        });
                    } else {
                        data.append(key, value === null ? '' : value);
                    }
                }
                if (this.file) {
                    data.append('file', this.file);
                }
                this.loading_submit = true
                this.$http.post(`/${this.resource}`, data)
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success(response.data.message)
                            this.$eventHub.$emit('reloadData')
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
            filterProvince() {
                this.form.province_id = null
                this.form.district_id = null
                this.filterProvinces()
            },
            filterProvinces() {
                this.provinces = this.all_provinces.filter(f => {
                    return f.department_id === this.form.department_id
                })
            },
            filterDistrict() {
                this.form.district_id = null
                this.filterDistricts()
            },
            filterDistricts() {
                this.districts = this.all_districts.filter(f => {
                    return f.province_id === this.form.province_id
                })
            },
            close() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
            clickAddAddress() {
                this.form.addresses.push({
                    'id': null,
                    'country_id': 'PE',
                    'location_id': [],
                    'address': null,
                    'is_default':0,
                    'establishment_code':'0000',
                    'is_active':1
                });
            },
            clickRemoveAddress(index) {
                this.form.addresses.splice(index, 1);
            },
            validateNumericInput(field) {
                
                if (this.form[field]) {
                    this.form[field] = this.form[field].replace(/[^\d-]/g, '');
                }
            },

        }
    }
</script>
