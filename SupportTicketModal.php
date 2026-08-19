<?php
/* ==========================================
   GET SUPPORT ISSUE STATUSES
   ========================================== */
    $statuses = [];
    $errorMessage = '';

    try 
    {
        $stmt = $conn->prepare(
            "CALL GetSupportIssueStatuses()"
        );

        $stmt->execute();

        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt->closeCursor();
    } 
    catch (PDOException $e) 
    {
        $statuses = [];

        $errorMessage =
            "Unable to retrieve support statuses.";
    }
?>
<div id="supportTicketModal" class="modal">
    <div class="modal-content">
        <!-- ==================================
             MODAL HEADER
             ================================== -->
        <div class="modal-header">
            <h2>Support Ticket</h2>
            <button type="button"
                    id="closeSupportTicketModal"
                    class="modal-close">
                &times;
            </button>
        </div>

        <!-- ==================================
             MODAL BODY
             ================================== -->
        <div class="modal-body">
            <!-- Ticket ID -->
            <input type="hidden" id="modalSupportIssueId">
            <div class="form-group">
                <label> Ticket ID</label>
                <input type="text" id="modalTicketId" readonly>
            </div>
            <!-- Name -->
            <div class="form-group">
                <label> Name </label>
                <input type="text" id="modalName" readonly>
            </div>
            <!-- Title -->
            <div class="form-group">
                <label> Title </label>
                <input type="text" id="modalTitle" readonly>
            </div>
            <!-- Fault Location -->
            <div class="form-group">
                <label> Fault Location </label>
                <input type="text" id="modalFaultLocation" readonly>
            </div>
            <!-- Description -->
            <div class="form-group">
                <label> Description </label>
                <textarea
                    id="modalDescription"
                    rows="5"
                    readonly></textarea>
            </div>
            <!-- Issue Date -->
            <div class="form-group">
                <label> Issue Date </label>
                <input type="text" id="modalIssueDate" readonly>
            </div>
            <!-- Status -->
            <div class="form-group">
                <label for="modalStatus"> Status </label>
                <select id="modalStatus">
                    <?php foreach ($statuses as $status): ?>
                        <option
                            value="<?= (int)$status['Id'] ?>">
                            <?= htmlspecialchars(
                                $status['Name']
                            ) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
			<!-- Completion Notes (Hidden by default; shown only when Status is 'Resolved') -->
			<div class="form-group" id="completionNotesGroup" style="display: none;">
				<label for="modalCompletionNotes"> Completion Notes <span style="color: red;">*</span></label>
				<textarea id="modalCompletionNotes" 
						  rows="4" 
						  placeholder="Enter details on how this ticket was resolved..."></textarea>
			</div>
            <!-- Assigned To -->
            <div class="form-group">
                <label for="modalAssignedTo">Assigned To</label>
				<input type="text" 
					   id="modalAssignedTo" 
					   readonly 
					   data-user-id="<?= (int)($_SESSION['UserId'] ?? 0) ?>" 
					   data-user-name="<?php echo htmlspecialchars(trim(($_SESSION['FirstName'] ?? '') . ' ' . ($_SESSION['LastName'] ?? ''))); ?>"
            </div>
        </div>

        <!-- ==================================
             MODAL FOOTER
             ================================== -->
        <div class="modal-footer">
            <button type="button" id="assignTicketButton" class="btn-secondary">Assign To Me</button>
            <button type="button"
                    id="saveTicketButton"
                    class="btn-primary">
                    Save Changes
            </button>
            <button type="button"
                    id="cancelTicketButton"
                    class="btn-secondary">
                    Close
            </button>
        </div>
    </div>
</div>