<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="x-ua-compatible" content="ie=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Invoice export</title>
</head>
<body>
<h1>Export invoices</h1>
<form>
	<table>
		<tr>
			<td>Organization:</td>
			<td>
				<select name="organization">
					<?php
						foreach ($organizations as $organization) {
							printf(
								'<option value="%d">%s</option>',
								$organization->id,
                                htmlspecialchars($organization->name, ENT_QUOTES)
							);
						}
					?>
				</select>
			</td>
		</tr>
		<tr>
			<td>Since:</td>
			<td><input type="date" name="since"></td>
		</tr>
		<tr>
			<td>Until:</td>
			<td><input type="date" name="until"></td>
		</tr>
		<tr>
			<td></td>
			<td style="text-align: right"><button type="submit">Export</button></td>
		</tr>
	</table>
</form>
</body>
</html>
