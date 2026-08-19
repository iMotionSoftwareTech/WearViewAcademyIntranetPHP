document.addEventListener("DOMContentLoaded", initialise);

function initialise() {
    setupLogout();
    setupModals();
    setupFormValidation();
    setupLoginForms();
    setupIssueStatusUpdate();
	setupJobSearch();
}

/* =========================
   LOGOUT
   ========================= */

function setupLogout() {
    const logoutLink = document.getElementById("logoutLink");

    if (!logoutLink) {
        return;
	}

    logoutLink.addEventListener("click", function (event) {

        event.preventDefault();

        window.location.href = "logout.php";
	});
}


/* =========================
   MODALS
   ========================= */

function setupModals() {
    const modal = document.getElementById("userModal");
    const newUserButton = document.getElementById("btnNewUser");

    // Guard clause: Exit if userModal doesn't exist on the current page
    if (!modal) {
        return;
    }

    // Ensure user modal is explicitly hidden on initialization
    modal.style.display = "none";

    // Open User Modal
    if (newUserButton) {
        newUserButton.addEventListener("click", function () {
            modal.style.display = "block";
        });
    }

    // Target the specific close button INSIDE userModal to avoid class collisions
    const closeButton = modal.querySelector(".close");
    if (closeButton) {
        closeButton.addEventListener("click", function () {
            modal.style.display = "none";
        });
    }

    // Close modal when clicking the dark background overlay
    window.addEventListener("click", function (event) {
        if (event.target === modal) {
            modal.style.display = "none";
        }
    });
}


/* =========================
   FORM VALIDATION
   ========================= */

function setupFormValidation() {
    const reportForm = document.getElementById("reportIssueForm");

    if (reportForm) {

        reportForm.addEventListener("submit", function (event) {

            if (!validateIssueForm()) {

                event.preventDefault();
			}
		});
	}
}


/* =========================
   SUPPORT ISSUE VALIDATION
   ========================= */

function validateIssueForm() {
    const name = document.getElementById("yourName");
    const email = document.getElementById("emailAddress");
    const location = document.getElementById("faultLocation");
    const faultType = document.getElementById("faultType");
    const title = document.getElementById("issueTitle");
    const description = document.getElementById("issueDescription");

    if (!name || !email || !location ||
        !faultType || !title || !description) {

        return true;
	}

    let valid = true;

    if (name.value.trim() === "") {
        name.setCustomValidity("Please enter your name.");
        valid = false;
	} else {
        name.setCustomValidity("");
	}


    if (!email.checkValidity()) {
        email.setCustomValidity("Please enter a valid email address.");
        valid = false;
	} else {

	email.setCustomValidity("");
}


    if (location.value.trim() === "") {
        location.setCustomValidity("Please enter the fault location.");
        valid = false;
	} else {
        location.setCustomValidity("");
	}

    if (faultType.value === "") {
        faultType.setCustomValidity("Please select a fault type.");
        valid = false;
	} else {
        faultType.setCustomValidity("");
	}

    if (title.value.trim() === "") {
        title.setCustomValidity("Please enter an issue title.");
        valid = false;
	} else {
        title.setCustomValidity("");
	}

    if (description.value.trim().length < 15) {
        description.setCustomValidity(
            "Please provide at least 15 characters."
        );

        valid = false;
	} else {
        description.setCustomValidity("");
	}

    if (!valid) {
        const firstInvalid =
            document.querySelector(":invalid");

        if (firstInvalid) {
            firstInvalid.reportValidity();
		}

        return false;
	}

    return true;
}

/* =========================
   SETUP LOGIN FORMS
   ========================= */
function setupLoginForms() {

    const loginContainer =
        document.getElementById("loginFormContainer");

    const registerContainer =
        document.getElementById("registerFormContainer");

    const showRegister =
        document.getElementById("showRegister");

    const showLogin =
        document.getElementById("showLogin");

    if (
        !loginContainer ||
        !registerContainer ||
        !showRegister ||
        !showLogin
    ) {
        return;
    }

    showRegister.addEventListener("click", function (event) {

        event.preventDefault();

        loginContainer.style.display = "none";
        registerContainer.style.display = "block";

    });

    showLogin.addEventListener("click", function (event) {

        event.preventDefault();

        registerContainer.style.display = "none";
        loginContainer.style.display = "block";

    });
}

/* =========================
   ISSUE STATUS UPDATE & MODAL
   ========================= */
function setupIssueStatusUpdate() {

    const modal = document.getElementById("supportTicketModal");
    const saveTicketButton = document.getElementById("saveTicketButton");
    const assignTicketButton = document.getElementById("assignTicketButton");
    const closeButtons = document.querySelectorAll("#closeSupportTicketModal, #cancelTicketButton");
    
    // Form Elements
    const statusSelect = document.getElementById("modalStatus");
    const notesGroup = document.getElementById("completionNotesGroup");
    const notesInput = document.getElementById("modalCompletionNotes");
    const assignedInput = document.getElementById("modalAssignedTo");

    // SET THIS TO YOUR DATABASE'S 'RESOLVED' STATUS ID
    const RESOLVED_STATUS_ID = "3"; 

    if (!modal) return;

    // Helper: Toggle Visibility of Completion Notes
    function checkStatusVisibility() {
        if (!statusSelect || !notesGroup) return;

        if (statusSelect.value === RESOLVED_STATUS_ID) {
            notesGroup.style.display = "block";
        } else {
            notesGroup.style.display = "none";
            if (notesInput) notesInput.value = ""; 
        }
    }

    if (statusSelect) {
        statusSelect.addEventListener("change", checkStatusVisibility);
    }

    // 1. OPEN MODAL & POPULATE DATA
    document.addEventListener("click", function (event) {
        const viewBtn = event.target.closest(".btn-view-ticket");
        if (!viewBtn) return;

        const jobCard = viewBtn.closest(".job-card");
        const ticketId = viewBtn.dataset.id || jobCard?.dataset.jobId;

        const title = jobCard?.querySelector("h3")?.innerText || "";
        const name = jobCard?.querySelector("p:nth-child(1)")?.innerText.replace("Name:", "").trim() || "";
        const location = jobCard?.querySelector("p:nth-child(2)")?.innerText.replace("Location:", "").trim() || "";
        const description = jobCard?.querySelector("p:nth-child(3)")?.innerText.replace("Description:", "").trim() || "";
        const date = jobCard?.querySelector("p:nth-child(4)")?.innerText.replace("Date:", "").trim() || "";

        const statusId = String(jobCard?.dataset.statusId || "");
        const assignedTo = jobCard?.dataset.assignedTo || "";
        const existingNotes = jobCard?.dataset.completionNotes || "";

        // Populate fields
        document.getElementById("modalSupportIssueId").value = ticketId;
        document.getElementById("modalTicketId").value = "#" + ticketId;
        document.getElementById("modalTitle").value = title;
        document.getElementById("modalName").value = name;
        document.getElementById("modalFaultLocation").value = location;
        document.getElementById("modalDescription").value = description;
        document.getElementById("modalIssueDate").value = date;
        
        if (assignedInput) assignedInput.value = assignedTo;
        if (statusSelect) statusSelect.value = statusId;
        if (notesInput) notesInput.value = existingNotes;

        checkStatusVisibility();

        // Check if ticket is resolved/completed
        const isCompleted = (statusId === RESOLVED_STATUS_ID);

        // Hide or show action buttons
        if (saveTicketButton) saveTicketButton.style.display = isCompleted ? "none" : "inline-block";
        if (assignTicketButton) assignTicketButton.style.display = isCompleted ? "none" : "inline-block";

        // Toggle read-only state on inputs
        if (statusSelect) statusSelect.disabled = isCompleted;
        if (notesInput) notesInput.readOnly = isCompleted;
        if (assignedInput) assignedInput.readOnly = isCompleted;

        modal.style.display = "block";
    });

    // 2. CLOSE MODAL
    closeButtons.forEach(btn => {
        btn.addEventListener("click", () => modal.style.display = "none");
    });

    // 3. ASSIGN TO ME
    if (assignTicketButton) {
        assignTicketButton.addEventListener("click", function () {
            if (assignedInput) {
                assignedInput.value = assignedInput.dataset.userName || "";
            }
        });
    }

    // 4. SAVE CHANGES
    if (saveTicketButton) {
        saveTicketButton.addEventListener("click", function () {

            const supportIssueId = document.getElementById("modalSupportIssueId")?.value || 0;
            const statusId = statusSelect?.value || 0;
            const assignedTo = assignedInput?.value || "";
            const completionNotes = notesInput?.value.trim() || "";

            if (statusId === RESOLVED_STATUS_ID && completionNotes === "") {
                alert("Please enter completion notes before marking this ticket as Resolved.");
                notesInput.focus();
                return;
            }

            const formData = new FormData();
            formData.append("SupportIssueId", supportIssueId);
            formData.append("StatusId", statusId);
            formData.append("AssignedTo", assignedTo);
            formData.append("CompletionNotes", completionNotes);

            fetch("updatesupportticket.php", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    modal.style.display = "none";
                    location.reload();
                } else {
                    alert(data.message || "Unable to update support ticket.");
                }
            })
            .catch(err => {
                console.error("Error updating support ticket:", err);
                alert("An error occurred while updating the ticket.");
            });
        });
    }
}

function setupJobSearch() {
    const jobSearchInput = document.getElementById("jobSearch");

    if (!jobSearchInput) return;

    jobSearchInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            event.preventDefault();
            
            const searchTerm = encodeURIComponent(this.value.trim());
            const urlParams = new URLSearchParams(window.location.search);
            
            // Keep the active tab (Incomplete or Completed)
            const currentTab = urlParams.get("tab") || "Incomplete";

            // Redirect with search query
            window.location.href = `viewjobs.php?tab=${currentTab}&search=${searchTerm}`;
        }
    });
}