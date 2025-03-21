// assets/js/script.js - Client-side interactive functionalities
document.addEventListener('DOMContentLoaded', function() {
    // Filter student table on coordinator dashboard
    var searchInput = document.getElementById('studentSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var filter = searchInput.value.toLowerCase();
            var rows = document.querySelectorAll('#studentTable tbody tr');
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = (text.indexOf(filter) > -1) ? '' : 'none';
            });
        });
    }
    // Toggle organization-specific fields on registration form
    var roleSelect = document.getElementById('role');
    var orgFields = document.getElementById('orgFields');
    if (roleSelect && orgFields) {
        function toggleOrgFields() {
            if (roleSelect.value === 'organization') {
                orgFields.style.display = 'block';
            } else {
                orgFields.style.display = 'none';
            }
        }
        roleSelect.addEventListener('change', toggleOrgFields);
        // Initialize visibility on page load
        toggleOrgFields();
    }
});
