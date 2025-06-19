
<?php
require_once '../db-config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nu ești autentificat']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch ($action) {
    case 'update_profile':
        $prenume = sanitize_input($_POST['prenume']);
        $nume = sanitize_input($_POST['nume']);
        $email = sanitize_input($_POST['email']);
        $telefon = sanitize_input($_POST['telefon']);
        
        $sql = "UPDATE utilizatori SET prenume='$prenume', nume='$nume', email='$email', telefon='$telefon' WHERE id=$user_id";
        if (mysqli_query($conn, $sql)) {
            $_SESSION['user_name'] = $prenume . ' ' . $nume;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $telefon;
            log_action($user_id, 'actualizare_profil', 'Profil actualizat');
            echo json_encode(['success' => true, 'message' => 'Profilul a fost actualizat']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea profilului']);
        }
        break;

    case 'add_address':
        $nume = sanitize_input($_POST['nume']);
        $strada = sanitize_input($_POST['strada']);
        $oras = sanitize_input($_POST['oras']);
        $judet = sanitize_input($_POST['judet']);
        $cod_postal = sanitize_input($_POST['cod_postal']);
        $telefon = sanitize_input($_POST['telefon']);
        $este_default = isset($_POST['este_default']) ? 1 : 0;
        
        if ($este_default) {
            mysqli_query($conn, "UPDATE adrese SET este_default=0 WHERE user_id=$user_id");
        }
        
        $sql = "INSERT INTO adrese (user_id, nume, strada, oras, judet, cod_postal, telefon, este_default) 
                VALUES ($user_id, '$nume', '$strada', '$oras', '$judet', '$cod_postal', '$telefon', $este_default)";
        
        if (mysqli_query($conn, $sql)) {
            log_action($user_id, 'adresa_noua', 'Adresă nouă adăugată');
            echo json_encode(['success' => true, 'message' => 'Adresa a fost adăugată']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la adăugarea adresei']);
        }
        break;

    case 'update_address':
        $id = (int)$_POST['id'];
        $nume = sanitize_input($_POST['nume']);
        $strada = sanitize_input($_POST['strada']);
        $oras = sanitize_input($_POST['oras']);
        $judet = sanitize_input($_POST['judet']);
        $cod_postal = sanitize_input($_POST['cod_postal']);
        $telefon = sanitize_input($_POST['telefon']);
        $este_default = isset($_POST['este_default']) ? 1 : 0;
        
        if ($este_default) {
            mysqli_query($conn, "UPDATE adrese SET este_default=0 WHERE user_id=$user_id");
        }
        
        $sql = "UPDATE adrese SET nume='$nume', strada='$strada', oras='$oras', judet='$judet', 
                cod_postal='$cod_postal', telefon='$telefon', este_default=$este_default 
                WHERE id=$id AND user_id=$user_id";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Adresa a fost actualizată']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea adresei']);
        }
        break;

    case 'delete_address':
        $id = (int)$_POST['id'];
        $sql = "DELETE FROM adrese WHERE id=$id AND user_id=$user_id";
        
        if (mysqli_query($conn, $sql)) {
            echo json_encode(['success' => true, 'message' => 'Adresa a fost ștearsă']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la ștergerea adresei']);
        }
        break;

    case 'get_addresses':
        $sql = "SELECT * FROM adrese WHERE user_id=$user_id ORDER BY este_default DESC, id DESC";
        $result = mysqli_query($conn, $sql);
        $addresses = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $addresses[] = $row;
        }
        echo json_encode(['success' => true, 'addresses' => $addresses]);
        break;

    case 'toggle_favorite':
        $produs_id = (int)$_POST['produs_id'];
        $check_sql = "SELECT id FROM favorite WHERE user_id=$user_id AND produs_id=$produs_id";
        $result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($result) > 0) {
            $sql = "DELETE FROM favorite WHERE user_id=$user_id AND produs_id=$produs_id";
            $action_log = 'scos_favorit';
        } else {
            $sql = "INSERT INTO favorite (user_id, produs_id) VALUES ($user_id, $produs_id)";
            $action_log = 'adaugat_favorit';
        }
        
        if (mysqli_query($conn, $sql)) {
            log_action($user_id, $action_log, "Produs ID: $produs_id");
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la actualizarea favoritelor']);
        }
        break;

    case 'get_favorites':
        $sql = "SELECT p.* FROM produse p 
                JOIN favorite f ON p.id = f.produs_id 
                WHERE f.user_id=$user_id AND p.activ=1";
        $result = mysqli_query($conn, $sql);
        $favorites = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $favorites[] = $row;
        }
        echo json_encode(['success' => true, 'favorites' => $favorites]);
        break;

    case 'add_review':
        $produs_id = (int)$_POST['produs_id'];
        $rating = (int)$_POST['rating'];
        $comentariu = sanitize_input($_POST['comentariu']);
        
        $sql = "INSERT INTO recenzii (user_id, produs_id, rating, comentariu) 
                VALUES ($user_id, $produs_id, $rating, '$comentariu')";
        
        if (mysqli_query($conn, $sql)) {
            log_action($user_id, 'recenzie_noua', "Produs ID: $produs_id");
            echo json_encode(['success' => true, 'message' => 'Recenzia a fost adăugată']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Eroare la adăugarea recenziei']);
        }
        break;

    case 'get_reviews':
        $produs_id = isset($_GET['produs_id']) ? (int)$_GET['produs_id'] : 0;
        if ($produs_id) {
            $sql = "SELECT r.*, u.prenume, u.nume FROM recenzii r 
                    JOIN utilizatori u ON r.user_id = u.id 
                    WHERE r.produs_id=$produs_id AND r.aprobat=1 
                    ORDER BY r.data_crearii DESC";
        } else {
            $sql = "SELECT r.*, u.prenume, u.nume, p.nume as produs_nume FROM recenzii r 
                    JOIN utilizatori u ON r.user_id = u.id 
                    JOIN produse p ON r.produs_id = p.id 
                    WHERE r.user_id=$user_id 
                    ORDER BY r.data_crearii DESC";
        }
        
        $result = mysqli_query($conn, $sql);
        $reviews = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $reviews[] = $row;
        }
        echo json_encode(['success' => true, 'reviews' => $reviews]);
        break;

    case 'get_loyalty_points':
        $sql = "SELECT puncte FROM puncte_fidelitate WHERE user_id=$user_id";
        $result = mysqli_query($conn, $sql);
        $points = mysqli_num_rows($result) > 0 ? mysqli_fetch_assoc($result)['puncte'] : 0;
        echo json_encode(['success' => true, 'points' => $points]);
        break;

    case 'get_vouchers':
        $sql = "SELECT v.* FROM vouchere v 
                JOIN utilizatori_vouchere uv ON v.id = uv.voucher_id 
                WHERE uv.user_id=$user_id AND v.activ=1 AND v.data_expirare >= CURDATE()";
        $result = mysqli_query($conn, $sql);
        $vouchers = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $vouchers[] = $row;
        }
        echo json_encode(['success' => true, 'vouchers' => $vouchers]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Acțiune necunoscută']);
        break;
}
?>
