<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="NutriPh_Import_Template.csv"');
echo "Last Name,First Name,M.I.,Gender,Grade Level,Section,Height,Weight,Height Unit,Weight Unit,Guardian Name,Guardian Number,Guardian Email\n";
echo "Dela Cruz,Juan,P,Male,Grade 3,Sampaguita,120,25,cm,kg,Maria Dela Cruz,09171234567,maria@email.com\n";
echo "Santos,Ana,M,Female,Grade 4,Rose,115,22,cm,kg,Pedro Santos,09181234567,pedro@email.com\n";
?>
