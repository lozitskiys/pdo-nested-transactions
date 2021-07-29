# Info

Example of nested transactions use:
```php

$db = new PdoWithNestedTransactions(/*credentials here*/);

try {
    $db->beginTransaction();

    // some logic here

    try {
        $db->beginTransaction();

        // some logic here

        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

    $db->commit();
} catch (Exception $e) {
    $db->rollBack();
    throw $e;
}
```