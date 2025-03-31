<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('scoreChart').getContext('2d');
        const scoreChart = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: ['0-25', '25-50', '50-75', '75-100'],
                datasets: [{
                    label: 'Scores Distribution',
                    data: [
                        <?php echo isset($score_counts['0_25']) ? $score_counts['0_25'] : 0; ?>,
                        <?php echo isset($score_counts['25_50']) ? $score_counts['25_50'] : 0; ?>,
                        <?php echo isset($score_counts['50_75']) ? $score_counts['50_75'] : 0; ?>,
                        <?php echo isset($score_counts['75_100']) ? $score_counts['75_100'] : 0; ?>
                    ],
                    backgroundColor: ['#ff6384', '#36a2eb', '#ffce56', '#4bc0c0']
                }]
            }
        });
    });
</script>