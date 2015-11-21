Feature: List files contained in a directory

Background:
Given an empty asset

Scenario: List existent repository
Given repository "mydir" exists
And I have a file "mydir/file1.txt"
And I have a file "mydir/file2.xml"
When I list "mydir"
Then I should get:
"""
mydir/file1.txt
mydir/file2.xml
"""

Scenario: List existent repository with filter
Given repository "mydir" exists
And I have a file "mydir/file1.txt"
And I have a file "mydir/file2.xml"
And I have a file "mydir/bidule.csv"
When I list "mydir" with filter "file"
Then I should get:
"""
mydir/file1.txt
mydir/file2.xml
"""

Scenario: List non existent repository
Given repository "anydir" does not exist
When I list "anydir"
Then I should get Exception 'Recommerce\Asset\Exception\AssetGetException'