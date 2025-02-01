<?php

if(isset($_FILES['nova_foto']) && $_FILES['nova_foto']['error'] === 0) {
    $nova_foto = file_get_contents($_FILES['nova_foto']['tmp_name']);
    $stmt = $conn->prepare("UPDATE pacientes SET nome=?, data_nascimento=?, cpf=?, 
                           telefone=?, email=?, endereco=?, foto=? WHERE id=?");
    $stmt->bind_param("sssssssi", $nome, $data_nascimento, $cpf, $telefone, 
                      $email, $endereco, $nova_foto, $id);
} else {
    $stmt = $conn->prepare("UPDATE pacientes SET nome=?, data_nascimento=?, cpf=?, 
                           telefone=?, email=?, endereco=? WHERE id=?");
    $stmt->bind_param("ssssssi", $nome, $data_nascimento, $cpf, $telefone, 
                      $email, $endereco, $id);
} 