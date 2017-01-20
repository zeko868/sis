<?php
require_once 'sesija.class.php';
require_once 'trenutno_vrijeme.php';
require_once 'dnevnik.php';

class Baza {

    const posluzitelj = "localhost";
    const korisnickoIme = "root";
    const lozinka = "";
    const baza = "WebDiP2015x076";

    private $veza = null;
    private $greska = '';

    function uspostaviVezu() {
        $this->veza = new mysqli(self::posluzitelj, self::korisnickoIme, self::lozinka, self::baza);
        if ($this->veza->connect_errno) {
            echo "Neuspješno spajanje na bazu: " . $this->veza->connect_errno . ", " . ($this->greska = $this->veza->connect_error);
        }
        $this->veza->set_charset("utf8");

        return $this->veza;
    }

    function prekiniVezu() {
        $this->veza->close();
    }

    private function pohraniIzvrseniUpit($upit) {
        $stmt = $this->veza->stmt_init();
        if (!$stmt->prepare("SELECT id FROM stranica_upit WHERE naziv=? LIMIT 1;")) {
            echo 'Pojavila se greška kod pripreme upita';
        }
        else {
            $stmt->bind_param('s', $upit);
            $stmt->execute();
            $skupZapisa = $stmt->get_result();
            $stmt->close();
            $idStranice;
            if ($skupZapisa->num_rows) {
                $idStranice = $skupZapisa->fetch_row()[0];
                $skupZapisa->free();
            }
            else {
                $stmt = $this->veza->stmt_init();
                if (!$stmt->prepare("INSERT INTO stranica_upit VALUES (null,?,'U');")) {
                    echo 'Pojavila se greška kod pripreme upita';
                }
                else {
                    $stmt->bind_param('s', $upit);
                    $stmt->execute();
                    $stmt->close();

                    $stmt = $this->veza->stmt_init();
                    if (!$stmt->prepare("SELECT id FROM stranica_upit WHERE naziv=? LIMIT 1;")) {
                        echo 'Pojavila se greška kod pripreme upita';
                    }
                    else {
                        $stmt->bind_param('s', $upit);
                        $stmt->execute();
                        $idStranice = $stmt->get_result()->fetch_row()[0];
                        $stmt->close();

                        $vrijeme = date('Y-m-d H:i:s', TrenutnoVrijeme());
                        $idKorisnika = Sesija::dajPodatak('id');
                        if ($idKorisnika===null) {
                            $idKorisnika=null;
                        }
                        $this->veza->query("INSERT INTO posjet_stranica_upit VALUES (null, $idStranice, $idKorisnika, '$vrijeme');");
                    }
                }
            }
        }
    }
    function izvrsiSelectUpit($upit, $params=null, $pohrani=true) {
        if ($pohrani) {
            ZapisiULog('Izvršavanje upita: ' . $upit, 'BP');
            $this->pohraniIzvrseniUpit($upit);            
        }
        $rezultat;
        if ($params===null) {
            $rezultat = $this->veza->query($upit);
        }
        else {
            $stmt = $this->veza->stmt_init();
            if (!$stmt->prepare($upit)) {
                echo 'Pojavila se greška kod pripreme upita';
            }
            else {
                $types = '';
                $params_for_bind_function = array();
                $params_for_bind_function[] = &$types;
                $paramNum = count($params);
                for ($i=0;$i<$paramNum;$i++) {
                    $typename = gettype($params[$i]);
                    switch ($typename) {
                        case 'integer':
                        case 'double':
                        case 'string':
                            $types .= $typename[0];
                            break;
                        case 'boolean':
                            $params[$i] = $params[$i] ? 1 : 0;
                            $types .= 'i';
                            break;
                        default:
                            $types .= 'b';
                    }
                    $params_for_bind_function[] = &$params[$i];
                }
                call_user_func_array(array($stmt, 'bind_param'), $params_for_bind_function);
                $stmt->execute();
                $rezultat = $stmt->get_result();
                $stmt->close();
            }
        }
        if (!$rezultat) {
            $rezultat = null;
        }
        return $rezultat;
    }

    function izvrsiNekiDrugiUpit($upit, $params=null, $pohrani=true) {
        if ($pohrani) {
            $this->pohraniIzvrseniUpit($upit);
            ZapisiULog('Izvršavanje upita: ' . $upit, 'BP');
        }
        $rezultat;
        if ($params===null) {
            $rezultat = $this->veza->query($upit);
        }
        else {
            $stmt = $this->veza->stmt_init();
            if (!$stmt->prepare($upit)) {
                echo 'Pojavila se greška kod pripreme upita';
            }
            else {
                $types = '';
                $params_for_bind_function = array();
                $params_for_bind_function[] = &$types;
                $paramNum = count($params);
                for ($i=0;$i<$paramNum;$i++) {
                    $typename = gettype($params[$i]);
                    switch ($typename) {
                        case 'integer':
                        case 'double':
                        case 'string':
                            $types .= $typename[0];
                            break;
                        case 'boolean':
                            $params[$i] = $params[$i] ? 1 : 0;
                            $types .= 'i';
                            break;
                        default:
                            $types .= 'b';
                    }
                    $params_for_bind_function[] = &$params[$i];
                }
                call_user_func_array(array($stmt, 'bind_param'), $params_for_bind_function);
                $stmt->execute();
                $rezultat = $stmt->get_result();
                $stmt->close();
            }
        }
        return $rezultat;
    }
    
    function postojiPogreska() {
        if ($this->greska != '') {
            return true;
        } else {
            return false;
        }
    }
    
    function dohvatiVezeTablice($nazivTablice='all') {
        $this->veza = new mysqli(self::posluzitelj, self::korisnickoIme, self::lozinka, 'information_schema');
        $this->veza->set_charset("utf8");
        $nazivBaze = self::baza;
        $rezultat;
        if ($nazivTablice==='all') {
            $rezultat = $this->veza->query("SELECT DISTINCT table_name,column_name,referenced_table_name,referenced_column_name FROM key_column_usage WHERE constraint_schema='$nazivBaze' AND referenced_table_name IS NOT NULL;");
        }
        else {
            $stmt = $this->veza->stmt_init();
            if (!$stmt->prepare("SELECT DISTINCT table_name,column_name,referenced_table_name,referenced_column_name FROM key_column_usage WHERE constraint_schema='$nazivBaze' AND referenced_table_name IS NOT NULL AND table_name=?;")) {

            }
            else {
                $stmt->bind_param('s', $nazivTablice);
                $stmt->execute();
                $rezultat = $stmt->get_result();
                $stmt->close();
            }
        }
        if (!$rezultat) {
            $rezultat = null;
        }
        $this->veza->close();
        return $rezultat;
    }
    
    function dohvatiBrojObuhvacenihRedovaProsleRadnje() {
        return $this->veza->affected_rows;
    }
    
    function sifraPogreske () {
        return $this->veza->errno;
    }

    function pozoviSpremljenuProceduru($naziv, $parametri) {
        for ($i=0;$i<count($parametri);$i++) {
            $parametri[$i] = $this->veza->escape_string($parametri[$i]);
        }
        $upit = 'CALL ' . $this->veza->escape_string($naziv) . ' (' . implode(',', $parametri) . ');';
        $rezultat = $this->veza->query($upit);

        if (!$rezultat) {
            $rezultat = null;
        }
        return $rezultat;
    }

}

?>