</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php foreach (($scriptsExtra ?? []) as $src): ?>
    <script src="<?= e($src) ?>"></script>
<?php endforeach; ?>
</body>
</html>
