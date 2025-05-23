@extends('admin.layout')
@section('content')
<div class="content-wrapper" id="app">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold">Categories Page</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="/admin/dashboard">Home</a></li>
                        <li class="breadcrumb-item active">Categories</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fs-5 font-weight-bold" id="exampleModalLabel">Categories</h3>
                    <button @click="resetForm()" type="button" class="btn-close" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent>
                        <div class="form-group">
                            <label>Category Name</label>
                            <input v-model="form.name" type="text" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Image</label>
                            <input ref="image_url" type="file" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea v-model="form.description" class="form-control" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button @click="resetForm()" type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancel</button>
                    <button v-if="status == 'add'" @click="addcategories()" type="button" class="btn btn-success">Save</button>
                    <button v-if="status == 'edit'" @click="editcategories()" type="button" class="btn btn-success">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-header">
                    <a @click="showModals()" href="#" class="btn btn-primary">
                        <i class="fa fa-plus-circle"></i> Add Category
                    </a>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr class="bg-primary text-white">
                                <th>#</th>
                                <th>Name</th>
                                <th>Image</th>
                                <th>Description</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(category, index) in product_list" :key="category.id">
                                <td>@{{ index + 1 }}</td>
                                <td>@{{ category.name }}</td>
                                <td>
                                    <img :src="'/storage/' + category.image_url" alt="Category Image" width="100">
                                </td>
                                <td>@{{ category.description }}</td>
                                <td>
                                    <a @click="getEdit(category)" class="btn btn-sm btn-success">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a @click="getDelete(category)" class="btn btn-sm btn-danger">
                                        <i class="fa fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{ $categories->links('vendor.pagination.bootstrap-5') }}
    </div>
</div>
@endsection

@section('script')
<script>
    new Vue({
        el: '#app',
        data: {
            status: 'add',
            product_list: [],
            form: {
                id: null,
                name: '',
                description: ''
            }
        },
        created() {
            this.fetchData();
        },
        methods: {
            fetchData() {
                axios.get('/admin/get-categories')
                    .then(res => this.product_list = res.data)
                    .catch(err => console.error(err));
            },
            showModals() {
                $('#exampleModal').modal('show');
            },
            closeModals() {
                $('#exampleModal').modal('hide');
            },
            resetForm() {
                this.status = 'add';
                this.form = { id: null, name: '', description: '' };
                if (this.$refs.image_url) {
                    this.$refs.image_url.value = '';
                }
                this.closeModals();
            },
            getEdit(item) {
                this.form.id = item.id;
                this.form.name = item.name;
                this.form.description = item.description;
                this.status = 'edit';
                if (this.$refs.image_url) {
                    this.$refs.image_url.value = '';
                }
                this.showModals();
            },
            getDelete(item) {
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        axios.post('/admin/delete-categories', { id: item.id })
                            .then(() => this.fetchData())
                            .catch(err => console.error(err));
                    }
                });
            },
            addcategories() {
                const formData = new FormData();
                formData.append('name', this.form.name);
                formData.append('description', this.form.description);
                if (this.$refs.image_url.files.length > 0) {
                    formData.append('image_url', this.$refs.image_url.files[0]);
                }
                axios.post('/admin/add-categories', formData)
                    .then(() => {
                        this.resetForm();
                        this.fetchData();
                    })
                    .catch(err => console.error(err));
            },
            editcategories() {
                const formData = new FormData();
                formData.append('id', this.form.id);
                formData.append('name', this.form.name);
                formData.append('description', this.form.description);
                if (this.$refs.image_url.files.length > 0) {
                    formData.append('image_url', this.$refs.image_url.files[0]);
                }
                axios.post('/admin/edit-categories', formData)
                    .then(() => {
                        this.resetForm();
                        this.fetchData();
                    })
                    .catch(err => console.error(err));
            }
        }
    });
</script>
@endsection
