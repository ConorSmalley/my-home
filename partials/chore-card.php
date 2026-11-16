
<?php
/**
 * Reusable Grocy chore card.
 *
 * Expected variable:
 * $chore - An associative array containing:
 *   chore_id
 *   chore_name
 *   description
 */
// var_dump($chore);
?>

<section class="card mb-3" style="max-width: 320px">
    <div class="card-body">
        <h4 class="card-title">
            <?= htmlspecialchars($chore['chore_name'] ?? 'Unnamed chore', ENT_QUOTES, 'UTF-8') ?>
        </h4>

        <p class="card-text">
            <?= htmlspecialchars($chore['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>
        </p>

        <form method="POST" class="d-inline">
            <input
                type="hidden"
                name="complete_chore_id"
                value="<?= (int) $chore['chore_id'] ?>"
            >

            <button
                type="submit"
                class="btn btn-success btn-sm"
                onclick="return confirm('Are you sure you want to complete this chore?')"
            >
                <i class="bi bi-check-circle"></i>
                Complete
            </button>
        </form>
    </div>
    <div class="card-footer fg-2">
        Due: <?= htmlspecialchars(formatChoreDueDate($chore['next_estimated_execution_time'])) ?>
    </div>
</section>
