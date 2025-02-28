<?php
require '../../ajaxconfig.php';

$response = array();

if (isset($_POST['group_id']) && isset($_POST['date'])) {
    $group_id = $_POST['group_id'];
    $date = $_POST['date'];
    $date = date('Y-m-d', strtotime($date)); // Convert the date to 'Y-m-d' format

    try {
        // Prepare the SQL query to fetch data
        $query = "SELECT 
                    gc.grp_name AS group_name,
                    ad.auction_month,
                    ad.date AS cal_date,
                    gc.chit_value,
                    ad.auction_value,
                    (gc.chit_value * (gc.commission / 100)) AS commission,
                    (gc.chit_value + (gc.chit_value * (gc.commission / 100)) - ad.auction_value) AS total_value,
                    (gc.chit_value + (gc.chit_value * (gc.commission / 100)) - ad.auction_value) / gc.total_members AS chit_amount
                  FROM auction_details ad
                  JOIN group_creation gc ON ad.group_id = gc.grp_id
                  WHERE ad.group_id = :group_id AND ad.date = :date";

        // Prepare and execute the statement
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
        $stmt->bindParam(':date', $date, PDO::PARAM_STR);
        $stmt->execute();

        // Fetch the result
        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            // Function to format number without trailing zero decimals
            function formatNumber($value) {
                if (is_numeric($value)) {
                    $formatted = number_format($value, 10, '.', ''); // Extra decimals to avoid rounding issues
                    $formatted = rtrim(rtrim($formatted, '0'), '.');
                    return $formatted;
                }
                return $value;
            }

            // Format the numbers
            $data['commission'] = formatNumber($data['commission']);
            $data['total_value'] = formatNumber($data['total_value']);
            $data['chit_amount'] = floor($data['chit_amount']);

            // Insert the chit_amount into auction_details table
            $insertQuery = "UPDATE auction_details 
                            SET chit_amount = :chit_amount
                            WHERE group_id = :group_id AND date = :date";

            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->bindParam(':chit_amount', $data['chit_amount'], PDO::PARAM_STR);
            $insertStmt->bindParam(':group_id', $group_id, PDO::PARAM_STR);
            $insertStmt->bindParam(':date', $date, PDO::PARAM_STR);
            $insertStmt->execute();

            echo json_encode($data);
        } else {
            echo json_encode([]);
        }

    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }

    // Close the PDO connection
    $pdo = null;
}

?>
