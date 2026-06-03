const db = require('../config/db');

const HomeController = {
  async index(req, res) {
    const cfgRes = await db.query(`SELECT valor FROM configuracoes WHERE chave = 'matriculas_abertas'`);
    const abertas = cfgRes.rows[0]?.valor === 'sim';
    res.render('index', { abertas });
  },
};

module.exports = HomeController;
