const db = require('../config/db');
const bcrypt = require('bcryptjs');

const DiretorModel = {
  async buscarPorUsuario(usuario) {
    const res = await db.query('SELECT * FROM diretor WHERE usuario = $1 AND ativo = TRUE', [usuario]);
    return res.rows[0] || null;
  },

  async criarOuAutenticar(usuario, senha) {
    const existente = await DiretorModel.buscarPorUsuario(usuario);
    if (!existente) {
      const hash = await bcrypt.hash(senha, 10);
      await db.query(
        `INSERT INTO diretor (usuario, senha, nome_completo) VALUES ($1, $2, $3)`,
        [usuario, hash, usuario]
      );
      return { ok: true, usuario };
    }
    const valido = await bcrypt.compare(senha, existente.senha);
    if (!valido) return null;
    await db.query('UPDATE diretor SET ultimo_acesso = NOW() WHERE usuario = $1', [usuario]);
    return { ok: true, usuario };
  },

  async matriculasAbertas() {
    const res = await db.query(`SELECT valor FROM configuracoes WHERE chave = 'matriculas_abertas'`);
    return res.rows[0]?.valor === 'sim';
  },

  async toggleMatriculas(estadoAtual) {
    const novoValor = estadoAtual ? 'não' : 'sim';
    await db.query(`UPDATE configuracoes SET valor = $1, atualizado_em = NOW() WHERE chave = 'matriculas_abertas'`, [novoValor]);
    return novoValor === 'sim';
  },
};

module.exports = DiretorModel;
