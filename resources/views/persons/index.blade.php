<div class="container mt-5">
    <h2>Persons List</h2>

    <!-- Add Multiple Users Form -->
    <div class="mb-3">
        <h4>Add Users</h4>
        <div id="userForm">
            <div class="row user-input">
                <div class="col">
                    <input type="text" class="form-control name" placeholder="Name">
                </div>
                <div class="col">
                    <input type="email" class="form-control email" placeholder="Email">
                </div>
                <div class="col">
                    <select class="form-control role">
                        <option value="Project Manager">Project Manager</option>
                        <option value="Team Lead">Team Lead</option>
                        <option value="Developer">Developer</option>
                    </select>
                </div>
                <div class="col">
                    <button class="btn btn-danger removeUser" onclick="removeUserField(this)">X</button>
                </div>
            </div>
        </div>
        <button class="btn btn-primary mt-2" onclick="addUserField()">Add More</button>
        <button class="btn btn-success mt-2" onclick="submitUsers()">Submit Users</button>
    </div>
    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div id="alertEditBox" class="alert d-none" role="alert"></div>

                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="mb-3">
                        <label for="editUserName" class="form-label">Name</label>
                        <input type="text" class="form-control" id="editUserName">
                    </div>
                    <div class="mb-3">
                        <label for="editUserEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editUserEmail">
                    </div>
                    <div class="mb-3">
                        <label for="editUserRole" class="form-label">Role</label>
                        <select class="form-control" id="editUserRole">
                            <option value="Project Manager">Project Manager</option>
                            <option value="Team Lead">Team Lead</option>
                            <option value="Developer">Developer</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Users Table -->
    <div id="alertBox" class="alert d-none" role="alert"></div>
    <table class="table table-bordered" id="personsTable">
        <thead>
            <tr>
                <th><input type="checkbox" id="selectAll"></th>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <button class="btn btn-danger mt-2" onclick="deleteSelectedUsers()">Delete Selected</button>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        authenticateUser().then(fetchPersons);
    });

    async function authenticateUser() {
        try {
            await fetch("{{ url('/sanctum/csrf-cookie') }}", {
                credentials: "include"
            });
            console.log("CSRF token set for authentication.");
        } catch (error) {
            console.error("CSRF token request failed:", error);
        }
    }

    async function fetchPersons() {
        try {
            const response = await fetch("{{ url('/api/persons') }}", {
                headers: {
                    "Authorization": "Bearer {{ auth()->user()->createToken('api-token')->plainTextToken }}",
                    "Accept": "application/json"
                },
                credentials: "include"
            });

            const data = await response.json();

            if (!data.data || !Array.isArray(data.data)) {
                throw new Error("Invalid data format received");
            }

            let tableBody = document.querySelector("#personsTable tbody");
            tableBody.innerHTML = "";

            data.data.forEach(person => {
                let row = `<tr>
                <td><input type="checkbox" class="userCheckbox" value="${person.id}"></td>
                <td>${person.id}</td>
                <td>${person.name}</td>
                <td>${person.email}</td>
                <td>${person.role}</td>
                <td>
                    <button class="btn btn-danger" onclick="deletePerson(${person.id})">Delete</button>
                    <button class="btn btn-primary" onclick="editPerson(${person.id}, '${person.name}', '${person.email}', '${person.role}')">Edit</button>
                </td>
            </tr>`;
                tableBody.innerHTML += row;
            });

            document.getElementById("selectAll").addEventListener("change", function() {
                document.querySelectorAll(".userCheckbox").forEach(cb => cb.checked = this.checked);
            });

        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    async function deletePerson(id) {
        if (!confirm("Are you sure you want to delete this user?")) return;

        try {
            let response = await fetch(`{{ url('/api/persons') }}/${id}`, {
                method: "DELETE",
                headers: {
                    "Authorization": "Bearer {{ auth()->user()->createToken('api-token')->plainTextToken }}",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                credentials: "include"
            });

            let result = await response.json();
            if (response.ok) {
                showAlert(result.message || "User deleted successfully!", "success");
                fetchPersons();
            } else {
                showAlert(result.message || "Failed to delete user!", "danger");
            }

        } catch (error) {
            showAlert("Error deleting user: " + error.message, "danger");
        }
    }

    async function deleteSelectedUsers() {
        let selectedUsers = Array.from(document.querySelectorAll(".userCheckbox:checked")).map(cb => cb.value);
        if (selectedUsers.length === 0) {
            showAlert("No users selected!", "warning");
            return;
        }

        if (!confirm("Are you sure you want to delete selected users?")) return;

        try {
            let response = await fetch("{{ url('/api/persons/bulk-delete') }}", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer {{ auth()->user()->createToken('api-token')->plainTextToken }}",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    ids: selectedUsers
                }),
                credentials: "include"
            });

            let result = await response.json();
            if (response.ok) {
                showAlert(result.message || "Users deleted successfully!", "success");
                fetchPersons();
            } else {
                showAlert(result.message || "Failed to delete users!", "danger");
            }

        } catch (error) {
            showAlert("Error deleting users: " + error.message, "danger");
        }
    }

    function addUserField() {
        let form = document.getElementById("userForm");
        let newRow = document.createElement("div");
        newRow.classList.add("row", "user-input");
        newRow.innerHTML = `
            <div class="col">
                <input type="text" class="form-control name" placeholder="Name">
            </div>
            <div class="col">
                <input type="email" class="form-control email" placeholder="Email">
            </div>
            <div class="col">
                <select class="form-control role">
                    <option value="Project Manager">Project Manager</option>
                    <option value="Team Lead">Team Lead</option>
                    <option value="Developer">Developer</option>
                </select>
            </div>
            <div class="col">
                <button class="btn btn-danger removeUser" onclick="removeUserField(this)">X</button>
            </div>
        `;
        form.appendChild(newRow);
    }

    function removeUserField(button) {
        button.parentElement.parentElement.remove();
    }

    async function submitUsers() {
        let users = [];
        document.querySelectorAll(".user-input").forEach(row => {
            let name = row.querySelector(".name").value;
            let email = row.querySelector(".email").value;
            let role = row.querySelector(".role").value;
            if (name && email && role) {
                users.push({
                    name,
                    email,
                    role
                });
            }
        });

        if (users.length === 0) {
            showAlert("No users to add!", "danger");
            return;
        }

        try {
            let response = await fetch("{{ url('/api/persons/bulk-create') }}", {
                method: "POST",
                headers: {
                    "Authorization": "Bearer {{ auth()->user()->createToken('api-token')->plainTextToken }}",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    users
                }),
                credentials: "include"
            });

            let result = await response.json();

            if (response.ok) {
                showAlert(result.message || "Users added successfully!", "success");
                fetchPersons();
            } else {

                let errorMessages = [];

                // Iterate through all validation errors dynamically
                if (result.errors) {
                    for (let key in result.errors) {
                        let match = key.match(/^users\.(\d+)\.(\w+)$/); // Match "users.0.email" or "users.1.name"
                        if (match) {
                            let index = parseInt(match[1]) + 1; // Convert index to 1-based
                            let field = match[2]; // Get field name (email, name, role)
                            errorMessages.push(`User ${index} (${field}): ${result.errors[key][0]}`);
                        }
                    }
                }

                // Show errors if there are any, otherwise show a default message
                if (errorMessages.length > 0) {
                    showAlert(errorMessages.join("\n"), "danger");
                } else {
                    showAlert(result.message || "Failed to add users!", "danger");
                }
            }
        } catch (error) {
            showAlert("Error adding users: " + error.message, "danger");
        }
    }

    function editPerson(id, name, email, role) {
        document.getElementById("editUserId").value = id;
        document.getElementById("editUserName").value = name;
        document.getElementById("editUserEmail").value = email;
        document.getElementById("editUserRole").value = role;
        new bootstrap.Modal(document.getElementById("editUserModal")).show();
    }

    async function updateUser() {
        let id = document.getElementById("editUserId").value;
        let name = document.getElementById("editUserName").value;
        let email = document.getElementById("editUserEmail").value;
        let role = document.getElementById("editUserRole").value;

        if (!id || !name || !email || !role) {
            showEditAlert("All fields are required!", "danger");
            return;
        }

        try {
            let response = await fetch(`{{ url('/api/persons') }}/${id}`, {
                method: "PUT",
                headers: {
                    "Authorization": "Bearer {{ auth()->user()->createToken('api-token')->plainTextToken }}",
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": "{{ csrf_token() }}"
                },
                body: JSON.stringify({
                    name,
                    email,
                    role
                }),
                credentials: "include"
            });

            let result = await response.json();
            if (response.ok) {
                showAlert(result.message || "User updated successfully!", "success");
                fetchPersons();
                bootstrap.Modal.getInstance(document.getElementById("editUserModal")).hide();
            } else {

                if (result.errors) {
                    console.log(result.errors);
                    let errorMessages = [];

                    for (let key in result.errors) {
                        if (key.startsWith("users.")) {
                            // Extract the index and increment by 1
                            let updatedKey = key.replace(/users\.(\d+)\./, (_, index) =>
                                `users.${parseInt(index) + 1}.`);
                            errorMessages.push(`${updatedKey}: ${result.errors[key][0]}`);
                        } else {
                            errorMessages.push(result.errors[key][0]);
                        }
                    }

                    // Show errors if there are any, otherwise show a default message
                    if (errorMessages.length > 0) {
                        showEditAlert(errorMessages.join("\n"), "danger");
                    } else {
                        showEditAlert(result.message || "Failed to add users!", "danger");
                    }
                }

            }

        } catch (error) {
            showEditAlert("Error updating user: " + error.message, "danger");
        }
    }

    function showAlert(message, type = "danger") {
        let alertBox = document.getElementById("alertBox");
        alertBox.innerHTML = message;
        alertBox.className = `alert alert-${type}`;
        alertBox.style.display = "block";

        setTimeout(() => {
            alertBox.style.display = "none";
        }, 5000);
    }

    function showEditAlert(message, type = "danger") {
        let alertBox = document.getElementById("alertEditBox");
        alertBox.innerHTML = message;
        alertBox.className = `alert alert-${type}`;
        alertBox.style.display = "block";

        setTimeout(() => {
            alertBox.style.display = "none";
        }, 5000);
    }
</script>
