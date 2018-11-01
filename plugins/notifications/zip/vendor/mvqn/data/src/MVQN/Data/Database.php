<?php
declare(strict_types=1);

namespace MVQN\Data;

/**
 * Class Database
 *
 * @package MVQN\Data
 * @author Ryan Spaeth <rspaeth@mvqn.net>
 * @final
 */
final class Database
{
    // =================================================================================================================
    // PROPERTIES
    // =================================================================================================================

    /** @var string|null The database hostname. */
    private static $databaseHost;

    /** @var int|null The database port number. */
    private static $databasePort;

    /** @var string|null The database username. */
    private static $databaseUser;

    /** @var string|null The database password. */
    private static $databasePass;

    /** @var string|null The database name. */
    private static $databaseName;

    /** @var \PDO|null The database object. */
    private static $pdo;

    // =================================================================================================================
    // METHODS: CONNECTION
    // =================================================================================================================

    /**
     * Attempts a connection to the database or simply returns an existing connection unless otherwise requested.
     *
     * @param string $host The host name where the database exists.
     * @param int $port The port number to which the database connection should be made.
     * @param string $dbname The database name.
     * @param string $user The username with access to the database.
     * @param string $pass The password for the provided username.
     * @param bool $reconnect If TRUE, then forces a new database (re-)connection to be made, defaults to FALSE.
     * @return null|\PDO Returns a valid database object for use with future database commands.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function connect(string $host = "", int $port = 0, string $dbname = "", string $user = "",
        string $pass = "", bool $reconnect = false): ?\PDO
    {
        // IF the connection already exists AND a reconnect was not requested...
        if(self::$pdo !== null && !$reconnect)
            // THEN return the current database object!
            return self::$pdo;

        // IF no hostname was provided AND hostname was not previously set, THEN throw an Exception!
        if($host === "" && (self::$databaseHost === null || self::$databaseHost === ""))
            throw new Exceptions\DatabaseConnectionException("A valid host name was not provided!");
        // OTHERWISE, set the hostname to the one provided or the previous one if none was provided.
        $host = $host ?: self::$databaseHost;

        // IF no port number was provided AND port number was not previously set, THEN throw an Exception!
        if($port === 0 && (self::$databasePort === null || self::$databasePort === 0))
            throw new Exceptions\DatabaseConnectionException("A valid port number was not provided!");
        // OTHERWISE, set the port number to the one provided or the previous one if none was provided.
        $port = $port ?: self::$databasePort;

        // IF no database name was provided AND database name was not previously set, THEN throw an Exception!
        if($dbname === "" && (self::$databaseName === null || self::$databaseName === ""))
            throw new Exceptions\DatabaseConnectionException("A valid database name was not provided!");
        // OTHERWISE, set the database name to the one provided or the previous one if none was provided.
        $dbname = $dbname ?: self::$databaseName;

        // IF no username was provided AND username was not previously set, THEN throw an Exception!
        if($user === "" && (self::$databaseUser === null || self::$databaseUser === ""))
            throw new Exceptions\DatabaseConnectionException("A valid username was not provided!");
        // OTHERWISE, set the username to the one provided or the previous one if none was provided.
        $user = $user ?: self::$databaseUser;

        // IF no password was provided AND password was not previously set, THEN throw an Exception!
        if($pass === "" && (self::$databasePass === null || self::$databasePass === ""))
            throw new Exceptions\DatabaseConnectionException("A valid password was not provided!");
        // OTHERWISE, set the password to the one provided or the previous one if none was provided.
        $pass = $pass ?: self::$databasePass;

        // All pre-checks should have ensured a valid state for connection!

        try
        {
            // Attempt to create a new database connection using the provided information.
            self::$pdo = new \PDO("pgsql:host=$host;port=$port;dbname=$dbname", $user, $pass, [
                // Setting some default options.
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);

            // IF the connection is valid, return the new database object!
            if(self::$pdo)
                return self::$pdo;
        }
        catch(\PDOException $e)
        {
            // OTHERWISE, throw an Exception!
            throw new Exceptions\DatabaseConnectionException($e->getMessage());
        }

        // We should NEVER reach this line of code, but if we somehow do, return NULL!
        return null;
    }

    // =================================================================================================================
    // METHODS: QUERYING
    // =================================================================================================================

    /**
     * Issues a SELECT query to the database.
     *
     * @param string $table The table for which to make the query.
     * @param array $columns An optional array of column names to be returned.
     * @param string $orderBy An optional ORDER BY suffix for sorting.
     * @return array Returns an associative array of rows from the database.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function select(string $table, array $columns = [], string $orderBy = ""): array
    {
        // Get a connection to the database.
        $pdo = self::connect();

        // Generate a SQL statement, given the provided parameters.
        $sql =
            "SELECT ".($columns === [] ? "*" : "\"".implode("\", \"", $columns)."\"")." FROM \"$table\"".
            ($orderBy !== "" ? " ORDER BY $orderBy" : "");

        // Execute the query.
        $results = $pdo->query($sql)->fetchAll();

        // Return the results!
        return $results;
    }

    /**
     * Issues a SELECT/WHERE query to the database.
     *
     * @param string $table The table for which to make the query.
     * @param string $where An optional WHERE clause to use for matching, when omitted a SELECT query is made instead.
     * @param array $columns An optional array of column names to be returned.
     * @param string $orderBy An optional ORDER BY suffix for sorting.
     * @return array Returns an associative array of matching rows from the database.
     * @throws Exceptions\DatabaseConnectionException
     */
    public static function where(string $table, string $where = "", array $columns = [], string $orderBy = ""): array
    {
        // Get a connection to the database.
        $pdo = self::connect();

        // Generate a SQL statement, given the provided parameters.
        $sql =
            "SELECT ".($columns === [] ? "*" : "\"".implode("\", \"", $columns)."\"")." FROM \"$table\"".
            ($where !== "" ? " WHERE $where"  : "").
            ($orderBy !== "" ? " ORDER BY $orderBy" : "");

        // Execute the query.
        $results = $pdo->query($sql)->fetchAll();

        // Return the results!
        return $results;
    }

}