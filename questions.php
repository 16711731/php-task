<?php
/*
# SPINDOGS TECHNICAL TASK

 * Please ensure that your coding style is consistent throughout
 * You will be scored on how elegant your solutions are for each question
 * The task should take around 60 mins in total, which leaves approx 5 mins per question
 * This task is designed to have a range of basic and complex questions - try to move
   through the more basic questions quickly in order to leave time for the more complex ones
 * If you get stuck on any question, please leave it and move on
 * Please type your answers below each question
 */

/*
QUESTION 1
 * Write some PHP code to do the following:
 * a) Process the following form once it has been submitted
 * b) Check that the email is a valid address
 * c) Create a new record in a database table called Users - you can assume a
database connection already exists - please ensure any SQL is secure

<form method="post" action="yourscript.php">
<input type="text" name="name">
<input type="text" name="email">
<input type="submit" name="submit" value="Submit">
</form>
 */

if (!empty($_POST)) {
    $email = $_POST["email"];
    $name = $_POST["name"];

    if (filter_var($email, FILTER_VALIDATE_EMAIL) !== false) {
        $statement = $dbh->prepare("INSERT INTO Users ('name', 'email') VALUES (:name, :email)");
        $statement->bindParam(':name', $name);
        $statement->bindParam(':email', $email);
        $statement->execute();
    }
}

/*
QUESTION 2
 * Based on the data table below, please provide MySQL examples for the following requests:
 * a) Get all results
 * b) Edit an existing record
 * c) Delete an existing record

+--------+-----------+--------+--------------+
|                   Users                    |
+--------+-----------+--------+--------------+
| id     | name      | gender | is_logged_in |
+--------+-----------+--------+--------------+
| 1      | Elizabeth | Female | 1            |
| 2      | Philip    | Male   | 0            |
| 3      | Charles   | Male   | 0            |
| 4      | William   | Male   | 0            |
| 5      | Henry     | Male   | 0            |
+--------+-----------+--------+--------------+
 */

$statement = $dbh->query("SELECT * FROM Users");
$statement = $dbh->query("UPDATE Users SET 'name' = 'Will' WHERE id = 4");
$statement = $dbh->query("DELETE FROM Users WHERE id = 4");

/*
QUESTION 3
 * Looking at the above data table, suggest some appropriate data types and indexes for the columns.
 */

/*
id
INT UNSIGNED NOT NULL AUTO_INCREMENT

name
VARCHAR(255) NULL DEFAULT NULL

gender
ENUM('Male', 'Female', 'Other') NOT NULL

is_logged_in
TINYINT(1) NOT NULL DEFAULT 0

Indexes
PRIMARY KEY (`id`)
INDEX `gender` (`gender`)
 */

/*
QUESTION 4
 * A new table (see below) links sports to a user. Write a MySQL query to return the
name of the person and their favourite sport. If they do not have a favourite sport,
their name should still appear in the list.

+-------------+----------+--------------+
|               UserSports              |
+-------------+----------+--------------+
| user_id     | sport    | is_favourite |
+-------------+----------+--------------+
| 1           | Tennis   | 1            |
| 2           | Football | 0            |
| 3           | Tennis   | 1            |
| 4           | Cricket  | 1            |
| 4           | Football | 0            |
| 4           | Rugby    | 0            |
| 5           | Rugby    | 1            |
+-------------+----------+--------------+
 */

$statement = $dbh->query(
    "
    SELECT u.name, IF(usp.is_favourite = 1, usp.sport, NULL) AS favourite_sport
    FROM Users u
    LEFT JOIN UserSports usp ON u.id = usp.user_id
    GROUP BY u.id
    ORDER BY u.name
    "
);

/*
QUESTION 5
 * Describe in your own words what you would need to do if you wanted to list the name of
each person alongside all the sports that they play separated by a comma (,)?
 */

/*
- Use the query result above
- You should get back a list of users and sports, with nulls for users without sports
- Initialise a new array to store user sports (starts empty)
- Start a foreach loop of the $users array as $user
- Every loop cycle, put the user name as an array index into the empty array
- The value of the array is an array of sports (push one to the list for every user sport, except when null)
- When the foreach is done, another loop is needed to concatenate the array of sports
- Implode the sports arrays by comma, converting each array to a comma separated string
 */

/*
QUESTION 6
 * Another table (see below) stores orders for an online shop, please provide MySQL examples
for the following requests:
 * a) Write a single query to return each user's name alongside their total_spend
 * b) Write a single query to return each user's name alongside their latest_order_total
 * c) Write a single query to return the name of each month alongside the total_monthly_income

+------------+---------+---------------------+-------+
|                       Orders                       |
+------------+---------+---------------------+-------+
| id         | user_id | date_ordered        | cost  |
+------------+---------+---------------------+-------+
| 1          | 1       | 2015-01-01 00:00:00 | 90.00 |
| 2          | 1       | 2015-02-30 00:00:00 | 7.00  |
| 3          | 2       | 2015-05-05 00:00:00 | 12.00 |
| 4          | 3       | 2015-05-20 00:00:00 | 50.00 |
+------------+---------+---------------------+-------+
 */

$userTotalSpend = $dbh->query(
    "
    SELECT u.name, SUM(o.cost) AS total_spend
    FROM Orders o
    JOIN Users u ON u.id = o.user_id
    GROUP BY u.id
    "
);

// 'greatest n per group'
$userLatestOrderTotal = $dbh->query(
    "
    SELECT u.name, latest_order.cost AS latest_order_total
    FROM
    (
        SELECT user_id, cost, MAX(date_ordered)
        FROM Orders
        GROUP BY user_id
    ) AS latest_order
    INNER JOIN Users u ON
    u.id = latest_order.user_id
    "
);

// Might be worth adding something like the following for a real report:
// WHERE YEAR(date_ordered) = YEAR(CURDATE()) 
$monthlyTotalIncome = $dbh->query(
    "
    SELECT DATE_FORMAT(date_ordered, '%M') AS month, SUM(cost) AS total_monthly_income
    FROM Orders o
    GROUP BY month
    ORDER BY date_ordered ASC
    "
);

/*
QUESTION 7
 * The following array contains a number of recipes and their corresponsing ingredients.
Please rewrite the array so that each **ingredient** also contains:
 * a) a unit price
 * b) a quantity
 */

$recipes = [
    'Spindogs Magic Drink' => [
        'Sugar',
        'Chocolate',
        'Squash',
        'Coffee',
    ],
    'Spindogs Punch' => [
        'Rum',
        'Vodka',
        'Orange Juice',
        'Lime',
    ],
];

/**
 * Recipe ingredients list
 * Cost: in pence (simpler to store currency as integer)
 * Amount: integer
 * Measurement unit: split out from the amount, to stop data pollution
 */

$recipes = [
    "Spindogs Magic Drink" => [
        "Sugar" => [
            "Cost" => 50,
            "Amount" => 100,
            "Unit" => "g",
        ],
        "Chocolate" => [
            "Cost" => 150,
            "Amount" => 100,
            "Unit" => "g",
        ],
        "Squash" => [
            "Cost" => 80,
            "Amount" => 100,
            "Unit" => "ml",
        ],
        "Coffee" => [
            "Cost" => 200,
            "Amount" => 100,
            "Unit" => "ml",
        ],
    ],
    "Spindogs Punch" => [
        "Rum" => [
            "Cost" => 180,
            "Amount" => 100,
            "Unit" => "gram",
        ],
        "Vodka" => [
            "Cost" => 150,
            "Amount" => 100,
            "Unit" => "ml",
        ],
        "Orange Juice" => [
            "Cost" => 90,
            "Amount" => 100,
            "Unit" => "ml",
        ],
        "Lime" => [
            "Cost" => 75,
            "Amount" => 1,
            "Unit" => "slice(s)",
        ],
    ],
];

/*
QUESTION 8
 * Write some PHP code to loop through your array and show the following:
 * a) Display the name of each recipe and list the ingredients
 * b) Display the cost of each recipe
 * c) Display the total cost of **all recipes**
 */

$totalCost = 0;

foreach ($recipes as $recipe => $ingredients) {
    $recipeCost = 0;

    echo $recipe . "\n";

    foreach ($ingredients as $ingredient => $value) {
        $totalCost += $ingredient['Cost'];
        $recipeCost += $ingredient['Cost'];
        echo "- " . $ingredient . " (" . $value['Amount'] . $value['Unit'] . ")\n";
    }

    echo "\nRecipe cost: £" . number_format(($recipeCost / 100), 2, '.', ' ') . "\n";
}

echo "\nTotal cost: £" . number_format(($totalCost / 100), 2, '.', ' ');

/*
QUESTION 9
 * Take a look at the example below. Please describe any security issues that you identify
and make a suggestion how the issue could be resolved.

<h1>Products page</h1>

<p>Hello <?= $_GET['name']; ?>, how are you today?</p>

<?php if (is_logged_in()) { ?>

<table>
<tr>
<td>Example product 1</td>
<td><a href="delete.php?id=1">Delete</a></td>
</tr>
<tr>
<td>Example product 2</td>
<td><a href="delete.php?id=2">Delete</a></td>
</tr>
</table>

<?php } ?>
 */

/*
- delete.php appears to be a GET http request
- delete.php accepts arbitrary data as a parameter
- delete.php can be used to delete any ID in the database, assuming there is no authorisation check
Solution: Make the delete buttons via a form that POSTs to the delete page, and add csrf token (if possible)
Also do a check on the serverside to make sure the user can delete the chosen id

- When echoing anything from $_REQUEST variables, they need to be escaped
Solution: You could escape the 'name', but I think it is better to display it from the session -
As the user will likely only have a name if they are logged in
*/

/*
QUESTION 10
 * Write some PHP code to list the date of each day, starting with the current date
and ending with the 10th day of the next month (in the format: Thursday 1st January 2015).
 */

$today = new DateTime();

// I don't think DateTime has a clean way of getting 10th day next month, so I did it in two steps
$endDate = new DateTime('first day of next month');
$endDate->modify($endDate->format('Y-m-10'));

$interval = DateInterval::createFromDateString('1 day');
$period = new DatePeriod($begin, $interval, $end);

foreach ($period as $day) {
    echo $day->format("l jS F Y\n");
}

/*
QUESTION 11
 * Describe in your own words the difference between a class and an object.
 */

/*
- Objects can have properties, methods, and have their own type
- Classes are like blueprints for objects, you can create an instance of an object using them
 */

/*
QUESTION 12
 * Write a single PHP class for a "Bear" (with approx. 50 lines of code). This is your opportunity
to demonstrate your OOP understanding and coding style. You get to determine what properties and
methods you use, but a "Bear" must be able to:
 * a) Eat honey every 2 hours and remember when they last had honey
 * b) Decide if they need to sleep
 */

/**
 * Bear
 * An entity that can be hungry and sleepy
 */
class Bear
{
    const HOURS_PER_EAT = 2;
    const HOURS_PER_SLEEP = 12;
    private $_timeLastEaten;
    private $_timeLastSlept;

    public function __construct()
    {
        $now = time();
        $this->_timeLastEaten = $now;
        $this->_timeLastSlept = $now;
    }

    // isHungry and isTired both do the same thing, we could decouple
    // the act of checking a bears sleep or eat status.
    private function isHungry()
    {
        $secondsPerEat = self::HOURS_PER_EAT * 1000;
        if (time() > ($this->_timeLastEaten + $secondsPerEat)) {
            return true;
        }
    }

    private function isTired()
    {
        $secondsPerSleep = self::HOURS_PER_SLEEP * 1000;
        if (time() > ($this->_timeLastSlept + $secondsPerSleep)) {
            return true;
        }
    }

    public function update()
    {
        if ($this->isHungry()) {
            $this->_timeLastEaten = time();
            echo "I will now eat\n";
        }

        if ($this->isTired()) {
            $this->_timeLastSlept = time();
            echo "I am tired\n";
        }
    }

}

$bear = new Bear();

// I think for a real world app this while loop would be replaced
// with a cron task or some other external event/scheduler
while (true) {
    $bear->update();
}
