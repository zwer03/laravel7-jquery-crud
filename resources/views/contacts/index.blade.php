@extends('layouts.app')

@section('content')
<div class="container">
    <div class="py-2">
        <a class="btn btn-primary" href="javascript:void(0)" onclick="create()"> Add Contact</a>
    </div>
    <div class="card">
        <div class="card-header">
            Contacts
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="contactList">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Contact Numbers</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($contacts as $contact)
                        <tr id="{{ $contact->id }}">
                            <td>{{ $contact->name }}</td>
                            <td>
                                @foreach ($contact->contactNumbers as $contact_number)
                                    @if ($loop->last)
                                        {{ $contact_number->number }}
                                    @else
                                        {{ $contact_number->number }},
                                    @endif
                                @endforeach
                            </td>
                            <td>
                                <a href="javascript:void(0)" data-id="<?php echo $contact->id ?>" data-original-title="Edit" class="btn btn-secondary btn-sm editContact">
                                    Edit
                                </a>
                                <a href="javascript:void(0)" data-id="<?php echo $contact->id ?>" data-original-title="Delete" class="btn btn-danger btn-sm deleteContact">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- modal -->
    <div class="modal fade" id="contactModal" tabindex="-1" role="dialog" aria-labelledby="contactModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="contactModalLabel">Modal title</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
                
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-primary" onclick="save()">Save</button>
            </div>
          </div>
        </div>
    </div>
</div>
    <script>
        var contactModal = $('#contactModal');
        var editedId = null;

        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        function create() {
            contactModal.find('.modal-title').text('New Contact');
            reset();
            contactModal.modal('show');
        }

        function save() {
            $.ajax({
                type: 'POST',
                url: "{{ route('contacts.store') }}",
                data: $('#contactForm').serialize(),
                dataType: 'json',
                success: function (data) {
                    if (data) {
                        numbers = [];

                        if (data.contact_numbers.length) {
                            data.contact_numbers.forEach(value => {
                                numbers.push(value.number);
                            });
                        }

                        if (!editedId) {
                            var newRecord = '';
                            newRecord += '<tr id=' + data.contact.id + '>';
                            newRecord += '<td>' + data.contact.name + '</td>';
                            newRecord += '<td>' + numbers.join(', ') + '</td>';
                            newRecord += '<td>';
                            newRecord += '<a href="javascript:void(0)" data-id="' + data.contact.id + '" data-original-title="Edit" class="btn btn-secondary btn-sm editContact">';
                            newRecord += 'Edit';
                            newRecord += '</a> ';
                            newRecord += '<a href="javascript:void(0)" data-id="' + data.contact.id + '" data-original-title="Delete" class="btn btn-danger btn-sm deleteContact">';
                            newRecord += 'Delete';
                            newRecord += '</a>';
                            newRecord += '</td>';
                            newRecord += '</tr>';

                            $('#contactList tbody').append(newRecord);
                        } else {
                            $('#contactList tbody tr#' + editedId).find('td').eq(0).text(data.contact.name);
                            $('#contactList tbody tr#' + editedId).find('td').eq(1).text(numbers.join(', '));
                        }
                    };

                    contactModal.modal('hide');
                },
                error: function (data) {
                    var response = JSON.parse(data.responseText);
                    var errorString = '<ul>';
                    $.each( response.errors, function( key, value) {
                        errorString += '<li>' + value + '</li>';
                    });
                    errorString += '</ul>';

                    $("#danger-alert").html('').append(errorString);
                    $("#danger-alert").fadeTo(2000, 500).slideUp(500, function() {
                        $("#danger-alert").slideUp(500);
                    });
                }
            })
        }

        function reset() {
            var modalForm = `
                <div class="alert alert-danger" id="danger-alert">
                </div>
                <form id="contactForm" name="contactForm" class="form-horizontal">
                    <input type="hidden" name="id">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="Enter Name">
                    </div>
        
                    <label for="contact_numbers">Contact Details</label>
                    <div class="form-group row" id="contactDetails">
                        <div class="col-sm-10">
                            <input type="text" class="form-control" id="contactNumbers" name="contact_numbers[][number]" placeholder="Enter Contact Number">
                        </div>
                        <div class="col-sm-2">
                            <a class="btn btn-primary" id="addContactNumber"><span>&plus;</span></a>
                        </div>
                    </div>
                </form>
            `;

            $('.modal-body').html('').append(modalForm);
            $("#danger-alert").hide();
            
            $('#addContactNumber').click(function(e) {
                var newContactInput = `
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="contactNumbers" name="contact_numbers[][number]" placeholder="Enter Contact Number">
                    </div>
                    <div class="col-sm-2">
                        <a type="button" class="btn btn-danger deleteContactNumber"><span aria-hidden="true">&times;</span></a>
                    </div>
                `;
                
                $('#contactDetails').append(newContactInput);
                e.preventDefault();
            });

            $('#contactForm').trigger('reset');
        }

        $('table').on('click', '.editContact', function () {
            contactModal.find('.modal-title').text('Edit Contact');
            contactModal.modal('show');
            reset();

            editedId = $(this).data('id');

            $.get("{{ route('contacts.index') }}" +'/' + editedId +'/edit', function (data) {
                $('input[name="id"]').val(data.id);
                $('input[name="name"]').val(data.name);

                if (data.contact_numbers.length) {
                    data.contact_numbers.forEach((value, index) => {
                        if (index == 0) {
                            $('#contactNumbers').val(value.number);
                        } else {
                            var newContactInput = '';
                            newContactInput += '<div class="col-sm-10">';
                            newContactInput += '<input type="text" class="form-control" id="contactNumbers" name="contact_numbers[][number]" value="' + value.number + '" placeholder="Enter Contact Number">';
                            newContactInput += '</div>';
                            newContactInput += '<div class="col-sm-2">';
                            newContactInput += '<button type="button" class="btn btn-danger deleteContactNumber"><span aria-hidden="true">&times;</span></button>';
                            newContactInput += '</div>';
                            
                            $('#contactDetails').append(newContactInput);
                        }
                    });
                }
            });
        });

        $('table').on('click', '.deleteContact', function () {
            if (!confirm('Are you sure?')) return;

            var id = $(this).data('id');
            var data = { 'id':id, '_method': 'delete' };

            $.ajax({
                type: 'POST',
                url: "{{ route('contacts.store') }}" + '/' + id,
                data: data,
                dataType: 'JSON',
                success: function () {
                    $('table tr#' + id).remove();
                }
            });
        });

        contactModal.on('click', '.deleteContactNumber', function () {
            $(this).parent().prev().remove();
            $(this).parent().remove();
        });

        contactModal.on('hidden.bs.modal', function () {
            editedId = null;
        });
    </script>

@endsection