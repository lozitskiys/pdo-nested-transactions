<?php

namespace PdoWithNT;

use Exception;
use PDO;

/**
 * Support for nested transactions in PDO.
 *
 * @author Stas Lozitskiy
 */
class PdoWithNT extends PDO
{
    protected $transLevel = 0;

    public function beginTransaction()
    {
        if ($this->transLevel == 0) {
            parent::beginTransaction();
        } else {
            $this->exec("SAVEPOINT LEVEL$this->transLevel");
        }
        $this->transLevel++;
    }

    public function commit()
    {
        try {
            $this->transLevel--;
            if ($this->transLevel == 0) {
                parent::commit();
            } else {
                $this->exec("RELEASE SAVEPOINT LEVEL$this->transLevel");
            }
        } catch (Exception $ex) {
            throw new Exception("Probably transaction already closed", 0, $ex);
        }
    }

    public function rollBack(Exception $exSrc = null)
    {
        try {
            $this->transLevel--;
            if ($this->transLevel == 0) {
                parent::rollBack();
            } else {
                $this->exec("ROLLBACK TO SAVEPOINT LEVEL$this->transLevel");
            }
        } catch (Exception $ex) {
            /** Misuse of transactions example:
             *
             * try {
             *     $db->beginTransaction();
             *
             *     try {
             *         throw Exception(); // - wrong!
             *
             *         $db->beginTransaction();
             *
             *         // ...
             *     } catch (Exception $e) {
             *         $db->rollBack();
             *         throw $e;
             *     }
             * } catch (Exception $e) {
             *     $db->rollBack();
             * }
             */

            $msg = "Error rolling back transaction. Maybe there is some Exception thrown before opening transaction";

            throw new Exception($msg, 0, $exSrc);
        }
    }
}
