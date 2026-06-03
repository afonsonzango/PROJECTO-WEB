const db = require('../config/db');
const bcrypt = require('bcryptjs');

const SecretariaModel = {
  async buscarPorUsuario(usuario) {
    const res = await db.query('SELECT * FROM secretaria WHERE usuario = $1 AND ativo = TRUE', [usuario]);
    return res.rows[0] || null;
  },

  async criarOuAutenticar(usuario, senha) {
    const existente = await SecretariaModel.buscarPorUsuario(usuario);
    if (!existente) {
      const hash = await bcrypt.hash(senha, 10);
      await db.query(
        `INSERT INTO secretaria (usuario, senha, nome_completo) VALUES ($1, $2, $3)`,
        [usuario, hash, usuario]
      );
      return { ok: true, usuario };
    }
    const valido = await bcrypt.compare(senha, existente.senha);
    if (!valido) return null;
    await db.query('UPDATE secretaria SET ultimo_acesso = NOW() WHERE usuario = $1', [usuario]);
    return { ok: true, usuario };
  },

  async alterarSenha(usuario, senhaAtual, senhaNova) {
    const sec = await SecretariaModel.buscarPorUsuario(usuario);
    if (!sec) return { erro: 'Usuário não encontrado.' };
    const valido = await bcrypt.compare(senhaAtual, sec.senha);
    if (!valido) return { erro: 'Senha atual incorreta.' };
    if (senhaNova.length < 6) return { erro: 'Nova senha deve ter pelo menos 6 caracteres.' };
    const hash = await bcrypt.hash(senhaNova, 10);
    await db.query('UPDATE secretaria SET senha = $1 WHERE usuario = $2', [hash, usuario]);
    return { ok: true };
  },
};

module.exports = SecretariaModel;
