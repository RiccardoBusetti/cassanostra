<?php
require_once 'main.php';

function getProductEANsList()
{
    $connection = connectToDB();
    $result = $connection->query("SELECT EAN_Prodotto FROM cnProdotto");

    if ($result == false)
        return null;
    else {
        $eanList = [];
        while ($row = $result->fetch_row())
            $eanList[] = $row[0];

        return $eanList;
    }
}

function registerSale($productId, $productAmount, $productPrice, $invoiceId, $cashierId)
{
    $connection = connectToDB();

    if ($statement = $connection->prepare("INSERT INTO cnVendita (FK_Prodotto, Quantita, PrezzoVendita, FK_Fattura, FK_Cassa, FK_UtenteCassiere) VALUES (?, ?, ?, ?, ?, ?)"))
    {
        $statement->bind_param("iidiis", $productId, $productAmount, $productPrice, $invoiceId, $cashierId, $_SESSION["username"]);
        $statement->execute();
        $statement->close();
    }

    // Ritorna l'ID della riga appena inserita oppure 0 se l'inserimento è fallito
    $insertId = $connection->insert_id;
    $connection->close();
    return $insertId;
}

function cancelSale($saleId): bool
{
    $cancellationSuccessful = false;
    $connection = connectToDB();

    if ($statement = $connection->prepare("UPDATE cnVendita SET Stornato=1 WHERE ID_Vendita = ?"))
    {
        $statement->bind_param("i", $saleId);
        $statement->execute();
        if ($statement->errno === 0)
            $cancellationSuccessful = true;

        $statement->close();
    }

    $connection->close();
    return $cancellationSuccessful;
}

function registerPurchase($productId, $productAmount, $productPrice, $invoiceId, $storeId)
{
    $registrationSuccessful = false;
    $connection = connectToDB();

    if ($statement = $connection->prepare("INSERT INTO cnAcquisto (FK_Prodotto, Quantita, PrezzoAcquisto, FK_Fattura, FK_PuntoVendita, FK_UtenteMagazziniere) VALUES (?, ?, ?, ?, ?, ?)"))
    {
        $statement->bind_param("iidiis", $productId, $productAmount, $productPrice, $invoiceId, $storeId, $_SESSION["username"]);
        $statement->execute();
        if ($statement->errno === 0)
            $registrationSuccessful = true;

        $statement->close();
    }

    $connection->close();
    return $registrationSuccessful;
}

function registerNewProduct($productName, $productBrand, $eanCode, $sellPrice): bool
{
    $registrationSuccessful = false;
    $connection = connectToDB();

    if ($statement = $connection->prepare("INSERT INTO cnProdotto (NomeProdotto, Produttore, EAN_Prodotto, PrezzoVenditaAttuale) VALUES (?, ?, ?, ?)"))
    {
        $statement->bind_param("sssd", $productName, $productBrand, $eanCode, $sellPrice);
        $statement->execute();
        if ($statement->errno === 0)
            $registrationSuccessful = true;

        $statement->close();
    }

    $connection->close();
    return $registrationSuccessful;
}

function updateProductPrice(string $eanCode, float $newPrice)
{
    $updateSuccessful = false;
    $connection = connectToDB();

    if ($statement = $connection->prepare("UPDATE cnProdotto SET PrezzoVenditaAttuale = ? WHERE EAN_Prodotto = ?"))
    {
        $statement->bind_param("ds", $newPrice, $eanCode);
        $statement->execute();
        if ($statement->errno === 0)
            $updateSuccessful = true;

        $statement->close();
    }

    $connection->close();
    return $updateSuccessful;
}

function getProductDetails(string $eanCode)
{
    $connection = connectToDB();

    if ($statement = $connection->prepare("SELECT ID_Prodotto, NomeProdotto, Produttore, EAN_Prodotto, PrezzoVenditaAttuale FROM cnProdotto WHERE EAN_Prodotto = ?"))
    {
        $statement->bind_param("s", $eanCode);
        $statement->execute();

        $result = $statement->get_result();
        $statement->close();
    }

    if ($result == false || $result->num_rows === 0)
        return null;
    else
        return $result->fetch_assoc();
}

function getProductsList()
{
    $connection = connectToDB();
    $result = $connection->query("SELECT NomeProdotto AS `Nome prodotto`, Produttore, EAN_Prodotto AS Barcode, CONCAT('€', PrezzoVenditaAttuale) AS `Prezzo di vendita unitario`
                                FROM cnProdotto
                                ORDER BY NomeProdotto ASC");

    if ($result == false)
        return null;
    else
        return $result->fetch_all(MYSQLI_ASSOC);
}

function getProductInventory($storeId, $nameOrEanFilter = null)
{
    $connection = connectToDB();

    if (empty($nameOrEanFilter))
    {
        if ($statement = $connection->prepare(
            "SELECT NomeProdotto AS `Nome prodotto`, EAN_Prodotto AS Codice, (ProdAcquistati.Tot-COALESCE(ProdVenduti.Tot, 0)) AS `Quantità disponibile`
            FROM
            (
                ((SELECT FK_Prodotto, SUM(COALESCE(Quantita, 0)) AS Tot FROM cnAcquisto WHERE FK_PuntoVendita = ? GROUP BY FK_Prodotto) AS ProdAcquistati)
                LEFT JOIN cnProdotto ON ProdAcquistati.FK_Prodotto = ID_Prodotto
            ) LEFT JOIN ((SELECT FK_Prodotto, SUM(COALESCE(Quantita, 0)) AS Tot FROM cnVendita, cnCassa WHERE Stornato = 0 AND FK_PuntoVendita = ? GROUP BY FK_Prodotto) AS ProdVenduti) ON ProdVenduti.FK_Prodotto = ID_Prodotto
            GROUP BY ID_Prodotto
            ORDER BY `Quantità disponibile` ASC"
        )) {
            $statement->bind_param("ii", $storeId, $storeId);
            $statement->execute();

            $result = $statement->get_result();
            $statement->close();
        }
    }
    else {
        if ($statement = $connection->prepare(
            "SELECT NomeProdotto AS `Nome prodotto`, EAN_Prodotto AS Codice, (ProdAcquistati.Tot-COALESCE(ProdVenduti.Tot, 0)) AS `Quantità disponibile`
            FROM
            (
                ((SELECT FK_Prodotto, SUM(COALESCE(Quantita, 0)) AS Tot FROM cnAcquisto WHERE FK_PuntoVendita = ? GROUP BY FK_Prodotto) AS ProdAcquistati)
                LEFT JOIN cnProdotto ON ProdAcquistati.FK_Prodotto = ID_Prodotto
            ) LEFT JOIN ((SELECT FK_Prodotto, SUM(COALESCE(Quantita, 0)) AS Tot FROM cnVendita, cnCassa WHERE Stornato = 0 AND FK_PuntoVendita = ? GROUP BY FK_Prodotto) AS ProdVenduti) ON ProdVenduti.FK_Prodotto = ID_Prodotto
            WHERE NomeProdotto LIKE ? OR EAN_Prodotto LIKE ?
            GROUP BY ID_Prodotto
            ORDER BY `Quantità disponibile` ASC"
        )) {
            $wildcardFilter = "%$nameOrEanFilter%";
            $statement->bind_param("iiss", $storeId, $storeId, $wildcardFilter, $wildcardFilter);
            $statement->execute();

            $result = $statement->get_result();
            $statement->close();
        }
    }

    if ($result == false)
        return null;
    else
        return $result->fetch_all(MYSQLI_ASSOC);
}