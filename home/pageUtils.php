<?php

require_once __DIR__ . '/../config/configHandler.php';
require 'NavbarTab.php';

// Definisce le tab per i vari ruoli
$tabs = [
    "ADM" => [
        new NavbarTab("Personalizzazione", "customization.php"),
        new NavbarTab("Gestione utenti", "users.php"),
        new NavbarTab("Gestione punti vendita", "stores.php")
    ],
    "DIR" => [
        new NavbarTab("Statistiche", "stats.php"),
        new NavbarTab("Cassieri", "cashiers.php"),
        new NavbarTab("Bilancio generale", "report.php")
    ]
];

/**
 * Stampa nella pagina il contenuto della navbar a seconda del ruolo dell'utente.
 * Se la home per quest'ultimo ha più tab, è possibile specificare quale tab viene automaticamente selezionata al caricamento della pagina.
 */
function printNavbar($userRole, $userFirstName, $userLastName, $selectedTab)
{
    global $tabs;

    // Barra principale
    $navbarHtml = '
    <nav class="nav-extended" style="background-color: ' . getAccentColor() . '">
        <div class="nav-wrapper">
            <a class="brand-logo left">' . getMarketName() . '</a>
            <a class="brand-logo center hide-on-small-and-down">' . getRoleName($userRole) . '</a>
            <ul id="nav-mobile" class="right">
                <li><i class="small material-icons">person</i></li>
                <li>' . "&ensp;$userFirstName $userLastName&ensp;" . '</li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>';

    // Tabs
    if (array_key_exists($userRole, $tabs))
    {
        $navbarHtml .= '<div class="nav-content">
                        <ul class="tabs tabs-transparent">';

        $index = 0;
        foreach ($tabs[$userRole] as $tab)
        {
            $navbarHtml .= "<li class=\"tab\"><a ";
            if (!empty($selectedTab) && $selectedTab == $index)
                $navbarHtml .= 'class="active" ';

            $navbarHtml .= "href=\"#{$index}\">{$tab->name()}</a></li>";
            $index++;
        }
        $navbarHtml .= '</ul></div>';
    }

    $navbarHtml .= '</nav>';
    echo $navbarHtml;
}

/**
 * Stampa il contenuto della pagina home relativa al ruolo utente specificato
 * @param $userRole string Il ruolo dell'utente
 */
function printPageContent($userRole)
{
    global $tabs;
    switch ($userRole)
    {
        case "MAG":
            $redirectUrl = "warehouse";
            break;
        case "ADM":
            $redirectUrl = "admin";
            break;
        case "DIR":
            $redirectUrl = "manager";
            break;
        case "CLI":
            $redirectUrl = "client";
            break;
        case "CAS":
            $redirectUrl = "cashier";
            break;
        case "FOR":
            $redirectUrl = "supplier";
            break;
    }

    // Il contenuto delle singole tab viene caricato tutto assieme, poi Materialize mostrerà soltanto il div della tab corrispondente.
    // Il codice JavaScript che inizializza le tab è in home/index.php. Per maggiori dettagli: materializecss.com/tabs.html
    if (array_key_exists($userRole, $tabs))
    {
        $index = 0;
        foreach ($tabs[$userRole] as $tab)
        {
            echo "<div id=\"$index\">";
            require "{$redirectUrl}/{$tab->page()}";
            echo "</div>";
            $index++;
        }
    }
    else
        require "{$redirectUrl}/index.php";
}

function getAccentColor() : string
{
    global $config;
    return $config["accentColor"];
}

function getMarketName(): string
{
    global $config;
    return $config["marketName"];
}

function getRoleName($userRole): string
{
    switch ($userRole)
    {
        case "MAG":
            return "Magazzino";
        case "ADM":
            return "Amministrazione";
        case "DIR":
            return "Direttore";
        case "CLI":
            return "Cliente";
        case "CAS":
            return "Cassiere";
        case "FOR":
            return "Fornitore";
        default:
            return "?";
    }
}