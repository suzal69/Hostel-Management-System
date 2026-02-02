// Student details functionality for manager pages
document.addEventListener('DOMContentLoaded', function() {
    // Wait for jQuery to be loaded
    var checkJQuery = setInterval(function() {
        if (typeof $ !== 'undefined') {
            clearInterval(checkJQuery);
            initStudentDetails();
        }
    }, 100);
    
    function initStudentDetails() {
        // Handle student selection to show details
        $('#roll_no').change(function() {
            var selectedRollNo = $(this).val();
            if (selectedRollNo) {
                // Fetch student details via AJAX
                $.ajax({
                    url: 'includes/get_student_details.php',
                    method: 'POST',
                    data: { roll_no: selectedRollNo },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.success) {
                            // Remove any existing details section
                            $('.student-details-section').remove();
                            
                            // Populate form fields based on the page
                            if (window.location.pathname.includes('vacate_rooms.php')) {
                                // For vacate_rooms.php - populate room details
                                populateVacateFormFields(data);
                            } else if (window.location.pathname.includes('change_room.php')) {
                                // For change_room.php - populate current room details
                                populateChangeFormFields(data);
                            }
                        } else {
                            // Clear form fields on error
                            clearFormFields();
                        }
                    },
                    error: function() {
                        // Clear form fields on error
                        clearFormFields();
                    }
                });
            } else {
                // Clear form fields when no student is selected
                clearFormFields();
            }
        });
        
        function populateVacateFormFields(data) {
            // For vacate_rooms.php - populate room and bed info
            $('#room_no').val(data.room_no && data.bed_number ? data.room_no + '(bed' + data.bed_number + ')' : (data.room_no || ''));
            $('#room_id').val(data.room_id || '');
            $('#bed_number').val(data.bed_number || '');
            $('#start_date').val(data.start_date || '');
            $('#end_date').val(data.end_date || '');
            
            // Show student details in a compact info section
            var infoHtml = '<div class="student-info-section" style="margin-top: 15px; padding: 10px; background: #e8f4fd; border-radius: 5px; border-left: 4px solid #0066cc;">' +
                '<strong><i class="fas fa-user"></i> Student:</strong> ' + data.name + ' (' + data.roll_no + ')' +
                '<br><strong><i class="fas fa-phone"></i> Contact:</strong> ' + data.contact +
                '<br><strong><i class="fas fa-home"></i> Current Room:</strong> ' + (data.room_no && data.bed_number ? data.room_no + '(bed' + data.bed_number + ')' : (data.room_no || 'Not Allocated')) +
                (data.start_date ? '<br><strong><i class="fas fa-calendar-alt"></i> Allocation Start:</strong> ' + data.start_date : '') +
                (data.end_date ? '<br><strong><i class="fas fa-calendar-check"></i> Allocation End:</strong> ' + data.end_date : '') +
                '</div>';
            
            $('.student-info-section').remove();
            $('.mail_grid_w3l form').append(infoHtml);
        }
        
        function populateChangeFormFields(data) {
            // For change_room.php - populate current room info
            $('#old_room_no').val(data.room_no && data.bed_number ? data.room_no + '(bed' + data.bed_number + ')' : (data.room_no || 'Not Allocated'));
            
            // Fetch available rooms and beds for the hostel
            fetchAvailableRoomsAndBeds();
            
            // Show student details in a compact info section
            var infoHtml = '<div class="student-info-section" style="margin-top: 15px; padding: 10px; background: #e8f4fd; border-radius: 5px; border-left: 4px solid #0066cc;">' +
                '<strong><i class="fas fa-user"></i> Student:</strong> ' + data.name + ' (' + data.roll_no + ')' +
                '<br><strong><i class="fas fa-phone"></i> Contact:</strong> ' + data.contact +
                '<br><strong><i class="fas fa-home"></i> Current Room:</strong> ' + (data.room_no && data.bed_number ? data.room_no + '(bed' + data.bed_number + ')' : (data.room_no || 'Not Allocated')) +
                '</div>';
            
            $('.student-info-section').remove();
            $('.mail_grid_w3l form').append(infoHtml);
        }
        
        function fetchAvailableRoomsAndBeds() {
            // Fetch available rooms via AJAX
            $.ajax({
                url: 'includes/get_available_rooms.php',
                method: 'POST',
                dataType: 'json',
                success: function(data) {
                    if (data && data.success) {
                        // Populate room dropdown
                        var roomSelect = $('#new_room_no');
                        roomSelect.empty().append('<option value="">Select New Room</option>');
                        
                        data.rooms.forEach(function(room) {
                            roomSelect.append('<option value="' + room.Room_id + '">' + room.Room_No + ' (' + room.available_beds + ' beds available)</option>');
                        });
                        
                        // Clear bed dropdown initially
                        $('#new_bed_no').empty().append('<option value="">Select Bed Number</option>');
                    }
                },
                error: function() {
                    console.error('Failed to fetch available rooms');
                }
            });
        }
        
        // Handle room selection to populate beds
        $(document).on('change', '#new_room_no', function() {
            var roomId = $(this).val();
            if (roomId) {
                // Fetch available beds for selected room
                $.ajax({
                    url: 'includes/get_available_beds.php',
                    method: 'POST',
                    data: { room_id: roomId },
                    dataType: 'json',
                    success: function(data) {
                        if (data && data.success) {
                            // Populate bed dropdown
                            var bedSelect = $('#new_bed_no');
                            bedSelect.empty().append('<option value="">Select Bed Number</option>');
                            
                            data.beds.forEach(function(bed) {
                                bedSelect.append('<option value="' + bed.bed_number + '">Bed ' + bed.bed_number + '</option>');
                            });
                        }
                    },
                    error: function() {
                        console.error('Failed to fetch available beds');
                    }
                });
            } else {
                // Clear bed dropdown when no room selected
                $('#new_bed_no').empty().append('<option value="">Select Bed Number</option>');
            }
        });
        
        function clearFormFields() {
            // Clear all form fields
            $('#room_no').val('');
            $('#room_id').val('');
            $('#bed_number').val('');
            $('#old_room_no').val('');
            $('#start_date').val('');
            $('#end_date').val('');
            
            // Remove info section
            $('.student-info-section').remove();
        }
    }
});
