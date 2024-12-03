@extends('layouts.app')

@section('title', 'Add Subject')

@section('content')
<div class="container">
    <!-- Success/Error Message -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card" style="background-color: #fff; border: 1px solid #cddfff;">
        <div class="card-body">
            <h5 class="card-title">Add Subject</h5>

            <!-- Form to add new subject -->
            <form method="POST" action="{{ route('subjects.store') }}">
                @csrf
                <div class="mb-3">
                    <label for="course_code" class="form-label">Subject Code</label>
                    <input type="text" class="form-control" id="course_code" name="course_code" required>
                </div>
                <div class="mb-3">
                    <label for="name" class="form-label">Subject Name</label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>

    <!-- Table to list added subjects -->
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Existing Subjects</h5>

            <!-- Table to display subjects -->
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Code</th>
                        <th>Subject Name</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($subjects as $subject)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $subject->course_code }}</td>
                            <td>{{ $subject->name }}</td>
                            <td>
                                <!-- Edit button (opens modal) -->
                                <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal{{$subject->id}}">
                                    Edit
                                </button>
                                <!-- Delete button (opens modal) -->
                                <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal{{$subject->id}}">
                                    Delete
                                </button>
                            </td>
                        </tr>

                        <!-- Edit Modal -->
                        <div class="modal fade" id="editModal{{$subject->id}}" tabindex="-1" aria-labelledby="editModalLabel{{$subject->id}}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="editModalLabel{{$subject->id}}">Edit Subject</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Edit Subject Form -->
                                        <form method="POST" action="{{ route('subjects.update', $subject->id) }}">
                                            @csrf
                                            @method('PUT')
                                            <div class="mb-3">
                                                <label for="course_code" class="form-label">Subject Code</label>
                                                <input type="text" class="form-control" id="course_code" name="course_code" value="{{ $subject->course_code }}" required>
                                            </div>
                                            <div class="mb-3">
                                                <label for="name" class="form-label">Subject Name</label>
                                                <input type="text" class="form-control" id="name" name="name" value="{{ $subject->name }}" required>
                                            </div>
                                            <button type="submit" class="btn btn-primary">Update</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal{{$subject->id}}" tabindex="-1" aria-labelledby="deleteModalLabel{{$subject->id}}" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="deleteModalLabel{{$subject->id}}">Confirm Deletion</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        Are you sure you want to delete the subject "{{ $subject->name }}"?
                                    </div>
                                    <div class="modal-footer">
                                        <form action="{{ route('subjects.destroy', $subject->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
