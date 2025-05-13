export default defineEventHandler(async (event) => {
  const ssn = getRouterParam(event, "ssn");
  const db = useDatabase();
});
