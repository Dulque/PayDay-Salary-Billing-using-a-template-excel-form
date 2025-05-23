// Role Switch Functionality

const adminBtn = document.getElementById('adminBtn');


const id=document.getElementById('id');

const userBtn = document.getElementById('userBtn');

const loginForm = document.getElementById('loginForm');


id.innerText="User ID:";


userBtn.addEventListener('click', () => {
    id.innerText = "User ID:";
   

userBtn.classList.add('active');

adminBtn.classList.remove('active');

loginForm.setAttribute('data-role', 'user');

});

adminBtn.addEventListener('click', () => {

    id.innerText = "Admin ID:";
adminBtn.classList.add('active');

userBtn.classList.remove('active');

loginForm.setAttribute('data-role', 'admin');

});



// Form Submission

loginForm.addEventListener('submit', (e) => {

e.defaultPrevented();



const userid = document.getElementById('userid').value;

const password = document.getElementById('password').value;

const role = loginForm.getAttribute('data-role');



/*if (!userid || !password) {

alert('Please fill in all fields.');

return;

}*/


const leaveForm = document.getElementById('leaveForm');

const requestHistoryBtn = document.getElementById('requestHistoryBtn');



// Form Submission

leaveForm.addEventListener('submit', (e) => {



const leaveType = document.getElementById('leaveType').value;

const startDate = document.getElementById('startDate').value;

const endDate = document.getElementById('endDate').value;

const reason = document.getElementById('reason').value;



alert(`Leave Request Submitted:\nType: ${leaveType}\nStart Date: ${startDate}\nEnd Date: ${endDate}\nReason: ${reason}`);

});



// Request History Button

requestHistoryBtn.addEventListener('click', () => {

alert('Redirecting to Leave Request History...');

// Add logic to redirect or display leave history

});
// Simulate login logic



});

document.addEventListener("DOMContentLoaded", function () {

    fetchBillHistory();
    
    });
    
    
    
    function fetchBillHistory() {
    
    fetch('fetch_bills.php')
    
    .then(response => response.json())
    
    .then(data => {
    
    const tableBody = document.querySelector("#billTable tbody");
    
    tableBody.innerHTML = ""; // Clear existing rows
    
    
    
    data.forEach(bill => {
    
    const row = document.createElement("tr");
    
    row.innerHTML = `
    
    <td>${bill.id}</td>
    
    <td>${bill.generated_date}</td>
    
    <td>${bill.salary_period}</td>
    
    <td>${bill.total_amount}</td>
    
    <td>${bill.status}</td>
    
    `;
    
    tableBody.appendChild(row);
    
    });
    
    })
    
    .catch(error => console.error("Error fetching bill history:", error));
    
    }