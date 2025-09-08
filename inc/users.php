<?php
/**
 * Liste des utilisateurs.
 * Chaque entrée : 'login' => ['password' => <hash>, 'role' => 'admin'|'user', 'disabled' => bool]
 * NB : Le compte 'admin' est l’admin principal et NE PEUT PAS être supprimé ni désactivé.
 */
$users = array (
  'admin' => 
  array (
    'password' => '$2y$10$YLGbpCzI4EEpbGhct9/Ts.WnUKLICfYxYW5I7oAWCR.iwcTacvUR2',
    'role' => 'admin',
    'disabled' => false,
  ),
  'etudiant' => 
  array (
    'password' => '$2y$10$IBSgPZeyFENf8x8LToYbuOCKrAwel1L6qwYQalqCb83X22ZuySnke',
    'role' => 'user',
    'disabled' => false,
  ),
);