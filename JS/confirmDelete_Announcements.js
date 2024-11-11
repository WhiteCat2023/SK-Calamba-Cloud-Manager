function confirmDelete() {
    const deleteBtn = document.getElementById('delete-btn');
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'No, keep it'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a new FormData object
            const formData = new FormData();
            formData.append('annsid', deleteBtn.value);
            formData.append('action', 'delete');
    
            // Use fetch API to send the request
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.ok) {
                    // If the response is OK, show a success message
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Announcement deleted successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        // Refresh the page or remove the row from the table
                        location.reload(); // Reload the page
                    });
                } else {
                    // Handle error response
                    Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                    Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
            });
        }
    });
}

