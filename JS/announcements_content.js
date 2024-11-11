// Function to show content based on the clicked item
function showContent(index) {
    console.log(index);
    const items = document.querySelectorAll('.list-group-item');
    
    const editId = document.getElementById('edit-id');
    const deleteBtn = document.getElementById('delete-btn');
    const editBtn = document.getElementById('edit-btn');
        
    const displayTitle = document.getElementById('display-title');
    const displayContent = document.getElementById('display-content');
    const displayTime = document.getElementById('display-time');
    const displayName = document.getElementById('display-name');
    const displayEmail = document.getElementById('display-email');
    
    const editTitle = document.getElementById('edit-title');
    const editContent = document.getElementById('edit-content');
    
    const list = document.getElementById('list');
    const contentContainer = document.getElementById('content-container');
        
    // Clear the current aria-current attribute from all items
    items.forEach(item => {
        item.removeAttribute("aria-current");
    });
        
    // Set the aria-current attribute for the clicked item
    const selectedItem = items[index];
    if (selectedItem) {
        selectedItem.setAttribute("aria-current", "true");
        deleteBtn.value = selectedItem.id;
        editBtn.value = selectedItem.id;
        editId.value = selectedItem.id;
        
        // Get the title and content from the selected item
        const title = selectedItem.querySelector('h6').textContent;
        const content = selectedItem.querySelector('p').textContent;
        
        // Hide the list and show the content container
        list.style.display = "none";
        contentContainer.style.display = "block";
        
        // Set the title and content in the display area
        displayTitle.innerHTML = title;
        displayContent.innerHTML = content;
        displayTime.innerHTML = smallItems(selectedItem, "time");
        displayName.innerHTML = smallItems(selectedItem, "created-by");
        displayEmail.innerHTML = smallItems(selectedItem, "created-by-email");
        
        editTitle.setAttribute("value", title);
        editContent.textContent = content;
    }
}

function smallItems(selectedItem, id){
    const small = selectedItem.querySelectorAll('small');
     
    for (let smallItem of small) {
        if(smallItem.id === id){
            return smallItem.textContent;
        }
    }
    
    return;
}
//function to return to list view
function returnList(){
    const back = document.getElementById('backBtn');

    const list = document.getElementById('list');
    const contentContainer = document.getElementById('content-container');

    back.addEventListener('click', () => {
        contentContainer.style.display = "none";
        list.style.display = "block";
    });
}
