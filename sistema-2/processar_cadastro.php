<?php

if(isset($_FILES['foto']) && $_FILES['foto']['error'] === 0) {
    $foto = file_get_contents($_FILES['foto']['tmp_name']);
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, data_nascimento, cpf, telefone, email, endereco, foto) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $nome, $data_nascimento, $cpf, $telefone, $email, $endereco, $foto);
} else {
    $stmt = $conn->prepare("INSERT INTO pacientes (nome, data_nascimento, cpf, telefone, email, endereco) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nome, $data_nascimento, $cpf, $telefone, $email, $endereco);
} 