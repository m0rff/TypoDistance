 # TypoDistance

 This class contains a function called typoDistance, which takes in two strings as arguments.
 This function finds the typo distance between the two strings, which is the likelihood that the second string is a typo of the first,as measured by a floating point number.
 The lower the number, the more likely it is that the second string is a typo of the first.
 Thus, for instance, "rlephants" is a fairly likely typo of "elephants" on a QWERTY keyboard, since R is fairly close to E, but "ilephants" is a less likely typo,
 and therefore would have a higher score.  One thing to note is that typoDistance is not commutative, since insertions are considered more costly than deletions.

 
This is a rewrite of https://github.com/wsong/Typo-Distance in PHP (tested with PHP 7.1) with support for the german QWERTZ keyboard layout and multibyte characters.