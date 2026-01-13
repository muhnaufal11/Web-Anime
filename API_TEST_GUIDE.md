<!-- Test API Autocomplete -->
<!-- Buka di browser: https://your-domain.com/api/search/suggestions?q=no -->

<?php

// Untuk test langsung tanpa tinker, gunakan ini di browser:
// https://nipnime.my.id/api/search/suggestions?q=no
// https://nipnime.my.id/api/search/suggestions?q=naruto
// https://nipnime.my.id/api/search/suggestions?q=game

// Expected response:
// {
//   "suggestions": [
//     {
//       "id": 1,
//       "title": "No Game No Life",
//       "slug": "no-game-no-life",
//       ...
//     }
//   ],
//   "count": 1
// }

// Jika response kosong atau error, check:
// 1. Database ada anime records
// 2. Score threshold tidak terlalu tinggi
// 3. Levenshtein distance calculation
// 4. Route sudah register dengan benar

?>
