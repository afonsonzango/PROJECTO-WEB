const db = require('../config/db');

function gerarIdOficial(nome) {
  const partes = nome.trim().toUpperCase().split(' ');
  const sigla = partes.length >= 2
    ? partes[0].slice(0, 2) + partes[partes.length - 1].slice(0, 2)
    : partes[0].slice(0, 4);
  const num = Math.floor(1000 + Math.random() * 9000);
  return sigla + num;
}

async function garantirIdUnico(nome) {
  let id;
  let existe = true;
  while (existe) {
    id = gerarIdOficial(nome);
    const res = await db.query('SELECT id FROM alunos WHERE id_oficial = $1', [id]);
    existe = res.rows.length > 0;
  }
  return id;
}

const AlunoModel = {
  async criar(dados) {
    const { nome, email, nif, sexo, curso, classe, periodo, telefone, escola_anterior, endereco, foto, certificado } = dados;
    const res = await db.query(
      `INSERT INTO alunos (nome, email, nif, sexo, curso, classe, periodo, telefone, escola_anterior, endereco, foto, certificado)
       VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9,$10,$11,$12) RETURNING id`,
      [nome, email, nif, sexo, curso, classe, periodo, telefone, escola_anterior, endereco || null, foto || null, certificado || null]
    );
    return res.rows[0];
  },

  async buscarPorEmailENome(email, nome) {
    const res = await db.query(
      `SELECT * FROM alunos WHERE LOWER(email) = LOWER($1) AND LOWER(nome) LIKE LOWER($2)`,
      [email, `%${nome}%`]
    );
    return res.rows[0] || null;
  },

  async buscarPorIdOficialEEmail(id_oficial, email) {
    const res = await db.query(
      `SELECT * FROM alunos WHERE LOWER(id_oficial) = LOWER($1) AND LOWER(email) = LOWER($2)`,
      [id_oficial, email]
    );
    return res.rows[0] || null;
  },

  async buscarPorId(id) {
    const res = await db.query('SELECT * FROM alunos WHERE id = $1', [id]);
    return res.rows[0] || null;
  },

  async listarTodos(filtro = null, busca = null) {
    let query = 'SELECT * FROM alunos';
    const params = [];
    if (busca) {
      params.push(`%${busca}%`);
      query += ` WHERE nome ILIKE $${params.length}`;
    } else if (filtro && ['Pendente', 'Aprovado', 'Recusado'].includes(filtro)) {
      params.push(filtro);
      query += ` WHERE estado = $${params.length}`;
    }
    query += ' ORDER BY id DESC';
    const res = await db.query(query, params);
    return res.rows;
  },

  async aprovar(id) {
    const aluno = await AlunoModel.buscarPorId(id);
    if (!aluno) return null;
    const idOficial = await garantirIdUnico(aluno.nome);
    await db.query(
      `UPDATE alunos SET estado = 'Aprovado', id_oficial = $1, data_aprovacao = NOW() WHERE id = $2`,
      [idOficial, id]
    );
    return idOficial;
  },

  async recusar(id, mensagem) {
    await db.query(
      `UPDATE alunos SET estado = 'Recusado', mensagem_recusa = $1 WHERE id = $2`,
      [mensagem, id]
    );
  },

  async contagens() {
    const res = await db.query(`
      SELECT
        COUNT(*) AS total,
        COUNT(*) FILTER (WHERE estado = 'Pendente') AS pendentes,
        COUNT(*) FILTER (WHERE estado = 'Aprovado') AS aprovados,
        COUNT(*) FILTER (WHERE estado = 'Recusado') AS recusados,
        COUNT(*) FILTER (WHERE status_reconfirmacao = 'Pendente') AS reconfirmacoes
      FROM alunos
    `);
    return res.rows[0];
  },

  async reconfirmacoesPendentes() {
    const res = await db.query(
      `SELECT * FROM alunos WHERE status_reconfirmacao = 'Pendente' ORDER BY ultima_reconfirmacao DESC`
    );
    return res.rows;
  },

  async aceitarReconfirmacao(id) {
    await db.query(`UPDATE alunos SET status_reconfirmacao = 'Aceita' WHERE id = $1`, [id]);
  },

  async recusarReconfirmacao(id) {
    await db.query(`UPDATE alunos SET status_reconfirmacao = 'Recusada' WHERE id = $1`, [id]);
  },
};

module.exports = AlunoModel;
